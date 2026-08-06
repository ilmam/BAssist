<?php

namespace App\Services;

use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\SwimlaneFlowStep;
use App\Models\Project;
use App\Models\StakeholderNeed;
use App\Models\Workspace;
use App\Support\ProjectContext;
use App\Support\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds a derived traceability matrix from FK / pivot links.
 * Chain: Objective ↔ Need ↔ Stakeholder Need → (Feature → Scenarios | Functional Requirement)
 * BPD coverage: FR|Feature.swimlane_flow_step_id ← SwimlaneFlowStep; steps optionally link upstream to SN.
 */
class TraceabilityMatrixService
{
    public function __construct(
        protected WorkspaceContext $workspaceContext,
        protected ProjectContext $projectContext,
    ) {
    }

    /**
     * @param  array{project_id?: int|string|null, orphans_only?: bool|string|null}  $filters
     * @return array{
     *   rows: list<array<string, mixed>>,
     *   summary: array{total: int, gaps: int, orphan_objectives: int, orphan_needs: int, orphan_stakeholder_needs: int, orphan_features: int, orphan_functional_requirements: int, features_without_scenarios: int, process_steps_without_need: int, uncovered_process_steps: int},
     *   projects: Collection<int, Project>,
     *   filters: array{project_id: int|null, workspace_id: int|null, workspace_name: string|null, orphans_only: bool}
     * }
     */
    public function build(array $filters = []): array
    {
        $projectId = isset($filters['project_id']) && is_numeric($filters['project_id'])
            ? (int) $filters['project_id']
            : $this->projectContext->id();
        $orphansOnly = filter_var($filters['orphans_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $workspaceId = $this->workspaceContext->id();
        $workspaceName = $workspaceId
            ? Workspace::query()->whereKey($workspaceId)->value('name')
            : null;

        $coverage = $this->processStepCoverage($projectId, $workspaceId);

        $rows = collect()
            ->merge($this->rowsFromNeeds($projectId, $workspaceId))
            ->merge($this->orphanObjectiveRows($projectId, $workspaceId))
            ->merge($this->orphanStakeholderNeedRows($projectId, $workspaceId))
            ->merge($this->orphanFeatureRows($projectId, $workspaceId))
            ->merge($this->orphanFunctionalRequirementRows($projectId, $workspaceId))
            ->merge($this->processStepGapRows($coverage))
            ->values();

        if ($orphansOnly) {
            $rows = $rows->filter(fn (array $row) => $row['has_gap'])->values();
        }

        $rows = $rows
            ->sortBy([
                ['project_name', 'asc'],
                ['objective_number', 'asc'],
                ['need_number', 'asc'],
                ['stakeholder_need_number', 'asc'],
                ['feature_code', 'asc'],
                ['functional_requirement_code', 'asc'],
                ['process_step_code', 'asc'],
            ])
            ->values();

        return [
            'rows' => $rows->all(),
            'summary' => [
                'total' => $rows->count(),
                'gaps' => $rows->where('has_gap', true)->count(),
                'orphan_objectives' => $rows->where('gap_type', 'orphan_objective')->count(),
                'orphan_needs' => $rows->filter(fn (array $r) => in_array('missing_objective', $r['gaps'], true))->count(),
                'orphan_stakeholder_needs' => $rows->where('gap_type', 'orphan_stakeholder_need')->count(),
                'orphan_features' => $rows->where('gap_type', 'orphan_feature')->count(),
                'orphan_functional_requirements' => $rows->where('gap_type', 'orphan_functional_requirement')->count(),
                'features_without_scenarios' => $rows->filter(
                    fn (array $r) => in_array('missing_scenarios', $r['gaps'], true)
                )->count(),
                'process_steps_without_need' => $rows->filter(
                    fn (array $r) => in_array('missing_step_stakeholder_need', $r['gaps'], true)
                )->count(),
                'uncovered_process_steps' => $rows->filter(
                    fn (array $r) => in_array('uncovered_process_step', $r['gaps'], true)
                )->count(),
            ],
            'projects' => $this->projectsForFilter($workspaceId),
            'filters' => [
                'project_id' => $projectId,
                'workspace_id' => $workspaceId,
                'workspace_name' => $workspaceName,
                'orphans_only' => $orphansOnly,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function rowsFromNeeds(?int $projectId, ?int $workspaceId): array
    {
        $needs = $this->scopedQuery(BusinessNeed::query(), $projectId, $workspaceId)
            ->with([
                'project:id,name,code',
                'businessObjectives:id,number,title',
                'stakeholderNeeds:id,number,title,project_id',
                'stakeholderNeeds.stakeholders:id,name',
                'stakeholderNeeds.features' => fn ($query) => $query
                    ->withCount('scenarios')
                    ->with(['swimlaneFlowStep.swimlaneFlow:id,title', 'swimlaneFlowStep.project:id,name,code'])
                    ->orderBy('number')
                    ->orderBy('title'),
                'stakeholderNeeds.functionalRequirements' => fn ($query) => $query
                    ->with(['swimlaneFlowStep.swimlaneFlow:id,title', 'swimlaneFlowStep.project:id,name,code'])
                    ->orderBy('number')
                    ->orderBy('title'),
                'stakeholderNeeds.changeRequests.features' => fn ($query) => $query
                    ->whereNull('stakeholder_need_id')
                    ->withCount('scenarios')
                    ->with(['swimlaneFlowStep.swimlaneFlow:id,title', 'swimlaneFlowStep.project:id,name,code'])
                    ->orderBy('number')
                    ->orderBy('title'),
                'stakeholderNeeds.changeRequests.functionalRequirements' => fn ($query) => $query
                    ->whereNull('stakeholder_need_id')
                    ->with(['swimlaneFlowStep.swimlaneFlow:id,title', 'swimlaneFlowStep.project:id,name,code'])
                    ->orderBy('number')
                    ->orderBy('title'),
            ])
            ->orderBy('number')
            ->orderBy('title')
            ->get();

        $rows = [];

        foreach ($needs as $need) {
            $objectives = $need->businessObjectives->isEmpty()
                ? [null]
                : $need->businessObjectives->all();
            $stakeholderNeeds = $need->stakeholderNeeds->isEmpty()
                ? [null]
                : $need->stakeholderNeeds->all();

            foreach ($objectives as $objective) {
                foreach ($stakeholderNeeds as $stakeholderNeed) {
                    [$features, $functionalRequirements] = $this->packagingForStakeholderNeed($stakeholderNeed);

                    if ($features->isEmpty() && $functionalRequirements->isEmpty()) {
                        $rows[] = $this->makeRow(
                            project: $need->project,
                            objective: $objective,
                            need: $need,
                            stakeholderNeed: $stakeholderNeed,
                            feature: null,
                            functionalRequirement: null,
                            processStep: null,
                            gapType: $objective === null || $stakeholderNeed === null
                                ? 'incomplete_chain'
                                : 'missing_feature',
                        );
                        continue;
                    }

                    foreach ($features as $feature) {
                        $rows[] = $this->makeRow(
                            project: $need->project,
                            objective: $objective,
                            need: $need,
                            stakeholderNeed: $stakeholderNeed,
                            feature: $feature,
                            functionalRequirement: null,
                            processStep: $feature->swimlaneFlowStep,
                            gapType: $this->featureRowGapType($objective, $feature),
                        );
                    }

                    foreach ($functionalRequirements as $functionalRequirement) {
                        $rows[] = $this->makeRow(
                            project: $need->project,
                            objective: $objective,
                            need: $need,
                            stakeholderNeed: $stakeholderNeed,
                            feature: null,
                            functionalRequirement: $functionalRequirement,
                            processStep: $functionalRequirement->swimlaneFlowStep,
                            gapType: $objective === null ? 'incomplete_chain' : null,
                        );
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function orphanObjectiveRows(?int $projectId, ?int $workspaceId): array
    {
        $objectives = $this->scopedQuery(BusinessObjective::query(), $projectId, $workspaceId)
            ->whereDoesntHave('businessNeeds')
            ->with('project:id,name,code')
            ->orderBy('number')
            ->orderBy('title')
            ->get();

        return $objectives->map(fn (BusinessObjective $objective) => $this->makeRow(
            project: $objective->project,
            objective: $objective,
            need: null,
            stakeholderNeed: null,
            feature: null,
            functionalRequirement: null,
            processStep: null,
            gapType: 'orphan_objective',
        ))->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function orphanStakeholderNeedRows(?int $projectId, ?int $workspaceId): array
    {
        $stakeholderNeeds = $this->scopedQuery(StakeholderNeed::query(), $projectId, $workspaceId)
            ->whereDoesntHave('businessNeeds')
            ->with([
                'project:id,name,code',
                'stakeholders:id,name',
                'features' => fn ($query) => $query
                    ->withCount('scenarios')
                    ->with(['swimlaneFlowStep.swimlaneFlow:id,title', 'swimlaneFlowStep.project:id,name,code'])
                    ->orderBy('number')
                    ->orderBy('title'),
                'functionalRequirements' => fn ($query) => $query
                    ->with(['swimlaneFlowStep.swimlaneFlow:id,title', 'swimlaneFlowStep.project:id,name,code'])
                    ->orderBy('number')
                    ->orderBy('title'),
                'changeRequests.features' => fn ($query) => $query
                    ->whereNull('stakeholder_need_id')
                    ->withCount('scenarios')
                    ->with(['swimlaneFlowStep.swimlaneFlow:id,title', 'swimlaneFlowStep.project:id,name,code'])
                    ->orderBy('number')
                    ->orderBy('title'),
                'changeRequests.functionalRequirements' => fn ($query) => $query
                    ->whereNull('stakeholder_need_id')
                    ->with(['swimlaneFlowStep.swimlaneFlow:id,title', 'swimlaneFlowStep.project:id,name,code'])
                    ->orderBy('number')
                    ->orderBy('title'),
            ])
            ->orderBy('number')
            ->orderBy('title')
            ->get();

        $rows = [];

        foreach ($stakeholderNeeds as $stakeholderNeed) {
            [$features, $functionalRequirements] = $this->packagingForStakeholderNeed($stakeholderNeed);

            if ($features->isEmpty() && $functionalRequirements->isEmpty()) {
                $rows[] = $this->makeRow(
                    project: $stakeholderNeed->project,
                    objective: null,
                    need: null,
                    stakeholderNeed: $stakeholderNeed,
                    feature: null,
                    functionalRequirement: null,
                    processStep: null,
                    gapType: 'orphan_stakeholder_need',
                );
                continue;
            }

            foreach ($features as $feature) {
                $rows[] = $this->makeRow(
                    project: $stakeholderNeed->project,
                    objective: null,
                    need: null,
                    stakeholderNeed: $stakeholderNeed,
                    feature: $feature,
                    functionalRequirement: null,
                    processStep: $feature->swimlaneFlowStep,
                    gapType: 'orphan_stakeholder_need',
                );
            }

            foreach ($functionalRequirements as $functionalRequirement) {
                $rows[] = $this->makeRow(
                    project: $stakeholderNeed->project,
                    objective: null,
                    need: null,
                    stakeholderNeed: $stakeholderNeed,
                    feature: null,
                    functionalRequirement: $functionalRequirement,
                    processStep: $functionalRequirement->swimlaneFlowStep,
                    gapType: 'orphan_stakeholder_need',
                );
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function orphanFeatureRows(?int $projectId, ?int $workspaceId): array
    {
        $features = $this->scopedQuery(Feature::query(), $projectId, $workspaceId)
            ->whereNull('stakeholder_need_id')
            ->whereNull('change_request_id')
            ->withCount('scenarios')
            ->with(['project:id,name,code', 'swimlaneFlowStep.swimlaneFlow:id,title', 'swimlaneFlowStep.project:id,name,code'])
            ->orderBy('number')
            ->orderBy('title')
            ->get();

        return $features->map(fn (Feature $feature) => $this->makeRow(
            project: $feature->project,
            objective: null,
            need: null,
            stakeholderNeed: null,
            feature: $feature,
            functionalRequirement: null,
            processStep: $feature->swimlaneFlowStep,
            gapType: 'orphan_feature',
        ))->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function orphanFunctionalRequirementRows(?int $projectId, ?int $workspaceId): array
    {
        $requirements = $this->scopedQuery(FunctionalRequirement::query(), $projectId, $workspaceId)
            ->whereNull('stakeholder_need_id')
            ->whereNull('change_request_id')
            ->with(['project:id,name,code', 'swimlaneFlowStep.swimlaneFlow:id,title', 'swimlaneFlowStep.project:id,name,code'])
            ->orderBy('number')
            ->orderBy('title')
            ->get();

        return $requirements->map(fn (FunctionalRequirement $requirement) => $this->makeRow(
            project: $requirement->project,
            objective: null,
            need: null,
            stakeholderNeed: null,
            feature: null,
            functionalRequirement: $requirement,
            processStep: $requirement->swimlaneFlowStep,
            gapType: 'orphan_functional_requirement',
        ))->all();
    }

    /**
     * Process/decision steps missing SN and/or elaborating FR|Feature.
     *
     * @param  array{without_need: list<SwimlaneFlowStep>, uncovered: list<SwimlaneFlowStep>}  $coverage
     * @return list<array<string, mixed>>
     */
    protected function processStepGapRows(array $coverage): array
    {
        $byId = [];

        foreach ($coverage['without_need'] as $step) {
            $byId[$step->id] = [
                'step' => $step,
                'missing_need' => true,
                'uncovered' => false,
            ];
        }

        foreach ($coverage['uncovered'] as $step) {
            if (! isset($byId[$step->id])) {
                $byId[$step->id] = [
                    'step' => $step,
                    'missing_need' => false,
                    'uncovered' => true,
                ];
            } else {
                $byId[$step->id]['uncovered'] = true;
            }
        }

        $rows = [];

        foreach ($byId as $entry) {
            /** @var SwimlaneFlowStep $step */
            $step = $entry['step'];
            $gapType = $entry['missing_need'] && $entry['uncovered']
                ? 'process_step_gap'
                : ($entry['missing_need'] ? 'missing_step_stakeholder_need' : 'uncovered_process_step');

            $rows[] = $this->makeRow(
                project: $step->project,
                objective: null,
                need: null,
                stakeholderNeed: $step->stakeholderNeed,
                feature: null,
                functionalRequirement: null,
                processStep: $step,
                gapType: $gapType,
                forceGaps: array_values(array_filter([
                    $entry['missing_need'] ? 'missing_step_stakeholder_need' : null,
                    $entry['uncovered'] ? 'uncovered_process_step' : null,
                ])),
            );
        }

        return $rows;
    }

    /**
     * @return array{without_need: list<SwimlaneFlowStep>, uncovered: list<SwimlaneFlowStep>}
     */
    protected function processStepCoverage(?int $projectId, ?int $workspaceId): array
    {
        $steps = $this->scopedQuery(SwimlaneFlowStep::query(), $projectId, $workspaceId)
            ->whereIn('type', SwimlaneMermaidGenerator::SATISFIABLE_TYPES)
            ->with([
                'project:id,name,code',
                'swimlaneFlow:id,title',
                'stakeholderNeed:id,number,title,project_id',
                'stakeholderNeed.stakeholders:id,name',
            ])
            ->orderBy('number')
            ->orderBy('label')
            ->get();

        $coveredIds = array_fill_keys(
            array_merge(
                $this->scopedQuery(Feature::query(), $projectId, $workspaceId)
                    ->whereNotNull('swimlane_flow_step_id')
                    ->pluck('swimlane_flow_step_id')
                    ->map(fn ($id) => (int) $id)
                    ->all(),
                $this->scopedQuery(FunctionalRequirement::query(), $projectId, $workspaceId)
                    ->whereNotNull('swimlane_flow_step_id')
                    ->pluck('swimlane_flow_step_id')
                    ->map(fn ($id) => (int) $id)
                    ->all(),
            ),
            true,
        );

        $withoutNeed = [];
        $uncovered = [];

        foreach ($steps as $step) {
            if ($step->stakeholder_need_id === null) {
                $withoutNeed[] = $step;
            }
            if (! isset($coveredIds[(int) $step->id])) {
                $uncovered[] = $step;
            }
        }

        return [
            'without_need' => $withoutNeed,
            'uncovered' => $uncovered,
        ];
    }

    public function countSwimlaneFlowStepsWithoutNeed(?int $projectId, ?int $workspaceId = null): int
    {
        return count($this->processStepCoverage($projectId, $workspaceId)['without_need']);
    }

    public function countUncoveredSwimlaneFlowSteps(?int $projectId, ?int $workspaceId = null): int
    {
        return count($this->processStepCoverage($projectId, $workspaceId)['uncovered']);
    }

    /**
     * @deprecated Use countSwimlaneFlowStepsWithoutNeed / countUncoveredSwimlaneFlowSteps.
     */
    public function countUnsatisfiedDesignSteps(?int $projectId, ?int $workspaceId = null): int
    {
        $coverage = $this->processStepCoverage($projectId, $workspaceId);

        return count(array_unique(array_map(
            fn (SwimlaneFlowStep $step) => $step->id,
            array_merge($coverage['without_need'], $coverage['uncovered']),
        )));
    }

    /**
     * @param  list<string>|null  $forceGaps
     * @return array<string, mixed>
     */
    protected function makeRow(
        ?Project $project,
        ?BusinessObjective $objective,
        ?BusinessNeed $need,
        ?StakeholderNeed $stakeholderNeed,
        ?Feature $feature,
        ?FunctionalRequirement $functionalRequirement,
        ?SwimlaneFlowStep $processStep,
        ?string $gapType,
        ?array $forceGaps = null,
    ): array {
        $gaps = $forceGaps ?? [];
        $scenarioCount = $feature !== null
            ? (int) ($feature->scenarios_count ?? 0)
            : 0;

        if ($forceGaps === null) {
            if ($objective === null && $need !== null) {
                $gaps[] = 'missing_objective';
            }
            if ($need === null && $objective !== null && $gapType === 'orphan_objective') {
                $gaps[] = 'missing_need';
            }
            if ($need === null && $stakeholderNeed !== null && $gapType === 'orphan_stakeholder_need') {
                $gaps[] = 'missing_need';
            }
            if ($stakeholderNeed === null && $need !== null) {
                $gaps[] = 'missing_stakeholder_need';
            }
            if ($feature === null && $functionalRequirement === null && $stakeholderNeed !== null) {
                $gaps[] = 'missing_feature';
            }
            if ($feature !== null && $scenarioCount === 0) {
                $gaps[] = 'missing_scenarios';
            }
            if ($gapType === 'orphan_objective') {
                $gaps[] = 'orphan_objective';
            }
            if ($gapType === 'orphan_stakeholder_need') {
                $gaps[] = 'orphan_stakeholder_need';
            }
            if ($gapType === 'orphan_feature') {
                $gaps[] = 'orphan_feature';
                $gaps[] = 'missing_stakeholder_need';
            }
            if ($gapType === 'orphan_functional_requirement') {
                $gaps[] = 'orphan_functional_requirement';
                $gaps[] = 'missing_stakeholder_need';
            }
        }

        $gaps = array_values(array_unique($gaps));

        $stakeholderNames = $stakeholderNeed?->relationLoaded('stakeholders')
            ? $stakeholderNeed->stakeholders->pluck('name')->filter()->values()->all()
            : [];

        return [
            'project_id' => $project?->id,
            'project_name' => $project?->name,
            'project_code' => $project?->code,
            'objective_id' => $objective?->id,
            'objective_number' => $objective?->number,
            'objective_code' => $objective?->code,
            'objective_title' => $objective?->title,
            'need_id' => $need?->id,
            'need_number' => $need?->number,
            'need_code' => $need?->code,
            'need_title' => $need?->title,
            'stakeholder_need_id' => $stakeholderNeed?->id,
            'stakeholder_need_number' => $stakeholderNeed?->number,
            'stakeholder_need_code' => $stakeholderNeed?->code,
            'stakeholder_need_title' => $stakeholderNeed?->title,
            'stakeholder_names' => $stakeholderNames,
            'feature_id' => $feature?->id,
            'feature_code' => $feature?->code,
            'feature_title' => $feature?->title,
            'scenarios_count' => $feature !== null ? $scenarioCount : null,
            'functional_requirement_id' => $functionalRequirement?->id,
            'functional_requirement_code' => $functionalRequirement?->code,
            'functional_requirement_title' => $functionalRequirement?->title,
            'process_step_id' => $processStep?->id,
            'process_step_flow_id' => $processStep?->swimlane_flow_id,
            'process_step_flow_title' => $processStep?->relationLoaded('swimlaneFlow')
                ? $processStep->swimlaneFlow?->title
                : null,
            'process_step_code' => $processStep?->code,
            'process_step_label' => $processStep?->label,
            'process_step_type' => $processStep?->type,
            // Backward-compatible aliases for export/BABOK blades during transition.
            'design_artifact_flow_id' => $processStep?->swimlane_flow_id,
            'design_artifact_flow_title' => $processStep?->relationLoaded('swimlaneFlow')
                ? $processStep->swimlaneFlow?->title
                : null,
            'design_artifact_code' => $processStep?->code,
            'design_artifact_label' => $processStep?->label,
            'design_artifact_type' => $processStep?->type,
            'gaps' => $gaps,
            'has_gap' => $gaps !== [],
            'gap_type' => $gapType,
        ];
    }

    protected function featureRowGapType(?BusinessObjective $objective, Feature $feature): ?string
    {
        if ($objective === null) {
            return 'incomplete_chain';
        }

        $scenarioCount = (int) ($feature->scenarios_count ?? 0);

        return $scenarioCount === 0 ? 'missing_scenarios' : null;
    }

    /**
     * SN-direct packaging plus CR-only packaging under this Stakeholder Need.
     *
     * @return array{0: Collection<int, Feature>, 1: Collection<int, FunctionalRequirement>}
     */
    protected function packagingForStakeholderNeed(?StakeholderNeed $stakeholderNeed): array
    {
        if ($stakeholderNeed === null) {
            return [collect(), collect()];
        }

        $features = $stakeholderNeed->relationLoaded('features')
            ? $stakeholderNeed->features
            : collect();
        $functionalRequirements = $stakeholderNeed->relationLoaded('functionalRequirements')
            ? $stakeholderNeed->functionalRequirements
            : collect();

        if ($stakeholderNeed->relationLoaded('changeRequests')) {
            foreach ($stakeholderNeed->changeRequests as $changeRequest) {
                if ($changeRequest->relationLoaded('features')) {
                    $features = $features->concat($changeRequest->features);
                }
                if ($changeRequest->relationLoaded('functionalRequirements')) {
                    $functionalRequirements = $functionalRequirements->concat($changeRequest->functionalRequirements);
                }
            }
        }

        return [
            $features->unique('id')->values(),
            $functionalRequirements->unique('id')->values(),
        ];
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function scopedQuery(Builder $query, ?int $projectId, ?int $workspaceId): Builder
    {
        $tenantId = auth()->user()?->tenant_id;

        if ($tenantId !== null) {
            $query->whereHas('project.workspace', function (Builder $workspaceQuery) use ($tenantId): void {
                $workspaceQuery->where('tenant_id', $tenantId);
            });
        }

        if ($workspaceId !== null) {
            $query->whereHas('project', function (Builder $projectQuery) use ($workspaceId): void {
                $projectQuery->where('workspace_id', $workspaceId);
            });
        }

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        return $query;
    }

    /**
     * @return Collection<int, Project>
     */
    protected function projectsForFilter(?int $workspaceId): Collection
    {
        $query = Project::query()->orderBy('name');

        $tenantId = auth()->user()?->tenant_id;
        if ($tenantId !== null) {
            $query->whereHas('workspace', function (Builder $workspaceQuery) use ($tenantId): void {
                $workspaceQuery->where('tenant_id', $tenantId);
            });
        }

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->get(['id', 'name', 'code', 'workspace_id']);
    }
}
