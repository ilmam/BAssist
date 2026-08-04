<?php

namespace App\Services;

use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\StakeholderNeed;
use App\Models\SwimlaneFlow;
use App\Models\Workspace;
use App\Support\ProcessStepSatisfyType;
use App\Support\ProjectContext;
use App\Support\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds a derived traceability matrix from FK / pivot links.
 * Chain: Objective ↔ Need ↔ Stakeholder Need → (Feature → Scenarios | Functional Requirement) → Design (BPD steps)
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
     *   summary: array{total: int, gaps: int, orphan_objectives: int, orphan_needs: int, orphan_stakeholder_needs: int, orphan_features: int, orphan_functional_requirements: int, features_without_scenarios: int, unsatisfied_design_artifacts: int},
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

        $designIndex = $this->designStepsIndex($projectId, $workspaceId);

        $rows = collect()
            ->merge($this->rowsFromNeeds($projectId, $workspaceId, $designIndex))
            ->merge($this->orphanObjectiveRows($projectId, $workspaceId))
            ->merge($this->orphanStakeholderNeedRows($projectId, $workspaceId, $designIndex))
            ->merge($this->orphanFeatureRows($projectId, $workspaceId, $designIndex))
            ->merge($this->orphanFunctionalRequirementRows($projectId, $workspaceId, $designIndex))
            ->merge($this->unsatisfiedDesignArtifactRows($designIndex))
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
                ['design_artifact_code', 'asc'],
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
                'unsatisfied_design_artifacts' => $rows->filter(
                    fn (array $r) => in_array('missing_satisfy', $r['gaps'], true)
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
     * @param  array{by_requirement: array<string, list<array<string, mixed>>>, unsatisfied: list<array<string, mixed>>}  $designIndex
     * @return list<array<string, mixed>>
     */
    protected function rowsFromNeeds(?int $projectId, ?int $workspaceId, array $designIndex): array
    {
        $needs = $this->scopedQuery(BusinessNeed::query(), $projectId, $workspaceId)
            ->with([
                'project:id,name,code',
                'businessObjectives:id,number,title',
                'stakeholderNeeds:id,number,title,project_id',
                'stakeholderNeeds.stakeholders:id,name',
                'stakeholderNeeds.features' => fn ($query) => $query
                    ->withCount('scenarios')
                    ->orderBy('number')
                    ->orderBy('title'),
                'stakeholderNeeds.functionalRequirements' => fn ($query) => $query
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
                    $features = $stakeholderNeed?->relationLoaded('features')
                        ? $stakeholderNeed->features
                        : collect();
                    $functionalRequirements = $stakeholderNeed?->relationLoaded('functionalRequirements')
                        ? $stakeholderNeed->functionalRequirements
                        : collect();

                    if ($features->isEmpty() && $functionalRequirements->isEmpty()) {
                        $rows[] = $this->makeRow(
                            project: $need->project,
                            objective: $objective,
                            need: $need,
                            stakeholderNeed: $stakeholderNeed,
                            feature: null,
                            functionalRequirement: null,
                            designArtifact: null,
                            gapType: $objective === null || $stakeholderNeed === null
                                ? 'incomplete_chain'
                                : 'missing_feature',
                        );
                        continue;
                    }

                    foreach ($features as $feature) {
                        foreach ($this->designArtifactsFor(ProcessStepSatisfyType::FEATURE, (int) $feature->id, $designIndex) as $design) {
                            $rows[] = $this->makeRow(
                                project: $need->project,
                                objective: $objective,
                                need: $need,
                                stakeholderNeed: $stakeholderNeed,
                                feature: $feature,
                                functionalRequirement: null,
                                designArtifact: $design,
                                gapType: $this->featureRowGapType($objective, $feature),
                            );
                        }
                    }

                    foreach ($functionalRequirements as $functionalRequirement) {
                        foreach ($this->designArtifactsFor(ProcessStepSatisfyType::FUNCTIONAL_REQUIREMENT, (int) $functionalRequirement->id, $designIndex) as $design) {
                            $rows[] = $this->makeRow(
                                project: $need->project,
                                objective: $objective,
                                need: $need,
                                stakeholderNeed: $stakeholderNeed,
                                feature: null,
                                functionalRequirement: $functionalRequirement,
                                designArtifact: $design,
                                gapType: $objective === null ? 'incomplete_chain' : null,
                            );
                        }
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
            designArtifact: null,
            gapType: 'orphan_objective',
        ))->all();
    }

    /**
     * @param  array{by_requirement: array<string, list<array<string, mixed>>>, unsatisfied: list<array<string, mixed>>}  $designIndex
     * @return list<array<string, mixed>>
     */
    protected function orphanStakeholderNeedRows(?int $projectId, ?int $workspaceId, array $designIndex): array
    {
        $stakeholderNeeds = $this->scopedQuery(StakeholderNeed::query(), $projectId, $workspaceId)
            ->whereDoesntHave('businessNeeds')
            ->with([
                'project:id,name,code',
                'stakeholders:id,name',
                'features' => fn ($query) => $query
                    ->withCount('scenarios')
                    ->orderBy('number')
                    ->orderBy('title'),
                'functionalRequirements' => fn ($query) => $query
                    ->orderBy('number')
                    ->orderBy('title'),
            ])
            ->orderBy('number')
            ->orderBy('title')
            ->get();

        $rows = [];

        foreach ($stakeholderNeeds as $stakeholderNeed) {
            $features = $stakeholderNeed->features;
            $functionalRequirements = $stakeholderNeed->functionalRequirements;

            if ($features->isEmpty() && $functionalRequirements->isEmpty()) {
                $rows[] = $this->makeRow(
                    project: $stakeholderNeed->project,
                    objective: null,
                    need: null,
                    stakeholderNeed: $stakeholderNeed,
                    feature: null,
                    functionalRequirement: null,
                    designArtifact: null,
                    gapType: 'orphan_stakeholder_need',
                );
                continue;
            }

            foreach ($features as $feature) {
                foreach ($this->designArtifactsFor(ProcessStepSatisfyType::FEATURE, (int) $feature->id, $designIndex) as $design) {
                    $rows[] = $this->makeRow(
                        project: $stakeholderNeed->project,
                        objective: null,
                        need: null,
                        stakeholderNeed: $stakeholderNeed,
                        feature: $feature,
                        functionalRequirement: null,
                        designArtifact: $design,
                        gapType: 'orphan_stakeholder_need',
                    );
                }
            }

            foreach ($functionalRequirements as $functionalRequirement) {
                foreach ($this->designArtifactsFor(ProcessStepSatisfyType::FUNCTIONAL_REQUIREMENT, (int) $functionalRequirement->id, $designIndex) as $design) {
                    $rows[] = $this->makeRow(
                        project: $stakeholderNeed->project,
                        objective: null,
                        need: null,
                        stakeholderNeed: $stakeholderNeed,
                        feature: null,
                        functionalRequirement: $functionalRequirement,
                        designArtifact: $design,
                        gapType: 'orphan_stakeholder_need',
                    );
                }
            }
        }

        return $rows;
    }

    /**
     * Features with no Stakeholder Need FK (matrix gap).
     *
     * @param  array{by_requirement: array<string, list<array<string, mixed>>>, unsatisfied: list<array<string, mixed>>}  $designIndex
     * @return list<array<string, mixed>>
     */
    protected function orphanFeatureRows(?int $projectId, ?int $workspaceId, array $designIndex): array
    {
        $features = $this->scopedQuery(Feature::query(), $projectId, $workspaceId)
            ->whereNull('stakeholder_need_id')
            ->withCount('scenarios')
            ->with('project:id,name,code')
            ->orderBy('number')
            ->orderBy('title')
            ->get();

        $rows = [];

        foreach ($features as $feature) {
            foreach ($this->designArtifactsFor(ProcessStepSatisfyType::FEATURE, (int) $feature->id, $designIndex) as $design) {
                $rows[] = $this->makeRow(
                    project: $feature->project,
                    objective: null,
                    need: null,
                    stakeholderNeed: null,
                    feature: $feature,
                    functionalRequirement: null,
                    designArtifact: $design,
                    gapType: 'orphan_feature',
                );
            }
        }

        return $rows;
    }

    /**
     * Functional requirements with no Stakeholder Need FK (matrix gap).
     *
     * @param  array{by_requirement: array<string, list<array<string, mixed>>>, unsatisfied: list<array<string, mixed>>}  $designIndex
     * @return list<array<string, mixed>>
     */
    protected function orphanFunctionalRequirementRows(?int $projectId, ?int $workspaceId, array $designIndex): array
    {
        $requirements = $this->scopedQuery(FunctionalRequirement::query(), $projectId, $workspaceId)
            ->whereNull('stakeholder_need_id')
            ->with('project:id,name,code')
            ->orderBy('number')
            ->orderBy('title')
            ->get();

        $rows = [];

        foreach ($requirements as $requirement) {
            foreach ($this->designArtifactsFor(ProcessStepSatisfyType::FUNCTIONAL_REQUIREMENT, (int) $requirement->id, $designIndex) as $design) {
                $rows[] = $this->makeRow(
                    project: $requirement->project,
                    objective: null,
                    need: null,
                    stakeholderNeed: null,
                    feature: null,
                    functionalRequirement: $requirement,
                    designArtifact: $design,
                    gapType: 'orphan_functional_requirement',
                );
            }
        }

        return $rows;
    }

    /**
     * Process/decision steps that do not satisfy any FR or Feature.
     *
     * @param  array{by_requirement: array<string, list<array<string, mixed>>>, unsatisfied: list<array<string, mixed>>}  $designIndex
     * @return list<array<string, mixed>>
     */
    protected function unsatisfiedDesignArtifactRows(array $designIndex): array
    {
        return collect($designIndex['unsatisfied'])
            ->map(fn (array $step) => $this->makeRow(
                project: $step['project'] ?? null,
                objective: null,
                need: null,
                stakeholderNeed: null,
                feature: null,
                functionalRequirement: null,
                designArtifact: $step,
                gapType: 'missing_satisfy',
            ))
            ->all();
    }

    /**
     * @param  array{by_requirement: array<string, list<array<string, mixed>>>, unsatisfied: list<array<string, mixed>>}  $designIndex
     * @return list<array<string, mixed>|null>
     */
    protected function designArtifactsFor(string $type, int $id, array $designIndex): array
    {
        $key = ProcessStepSatisfyType::encode($type, $id);
        $steps = $designIndex['by_requirement'][$key] ?? [];

        return $steps !== [] ? $steps : [null];
    }

    /**
     * Count process/decision BPD steps that do not satisfy a Feature or FR.
     */
    public function countUnsatisfiedDesignSteps(?int $projectId, ?int $workspaceId = null): int
    {
        return count($this->designStepsIndex($projectId, $workspaceId)['unsatisfied']);
    }

    /**
     * Flatten swimlane process/decision elements into an index by satisfied requirement.
     *
     * @return array{by_requirement: array<string, list<array<string, mixed>>>, unsatisfied: list<array<string, mixed>>}
     */
    protected function designStepsIndex(?int $projectId, ?int $workspaceId): array
    {
        $flows = $this->scopedQuery(SwimlaneFlow::query(), $projectId, $workspaceId)
            ->with('project:id,name,code')
            ->orderBy('title')
            ->get();

        $validFeatureIds = $this->scopedQuery(Feature::query(), $projectId, $workspaceId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $validFeatureIds = array_fill_keys($validFeatureIds, true);

        $validFrIds = $this->scopedQuery(FunctionalRequirement::query(), $projectId, $workspaceId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $validFrIds = array_fill_keys($validFrIds, true);

        $byRequirement = [];
        $unsatisfied = [];

        $mermaid = app(SwimlaneMermaidGenerator::class);

        foreach ($flows as $flow) {
            $elements = $mermaid->normalizeElements(is_array($flow->elements) ? $flow->elements : []);

            foreach ($elements as $element) {
                $type = $element['type'];
                if (! in_array($type, SwimlaneMermaidGenerator::SATISFIABLE_TYPES, true)) {
                    continue;
                }

                $label = $element['label'];
                $satisfyType = $element['satisfy_type'];
                $satisfyId = $element['satisfy_id'];

                $step = [
                    'project' => $flow->project,
                    'flow_id' => $flow->id,
                    'flow_title' => $flow->title,
                    'code' => $element['code'],
                    'label' => $label,
                    'type' => $type,
                    'satisfy_type' => $satisfyType,
                    'satisfy_id' => $satisfyId,
                ];

                $targetExists = false;
                if ($step['satisfy_type'] === ProcessStepSatisfyType::FEATURE && $step['satisfy_id'] !== null) {
                    $targetExists = isset($validFeatureIds[$step['satisfy_id']]);
                } elseif ($step['satisfy_type'] === ProcessStepSatisfyType::FUNCTIONAL_REQUIREMENT && $step['satisfy_id'] !== null) {
                    $targetExists = isset($validFrIds[$step['satisfy_id']]);
                }

                if (! $targetExists) {
                    $step['satisfy_type'] = null;
                    $step['satisfy_id'] = null;
                    $unsatisfied[] = $step;
                    continue;
                }

                $key = ProcessStepSatisfyType::encode($step['satisfy_type'], $step['satisfy_id']);
                $byRequirement[$key] ??= [];
                $byRequirement[$key][] = $step;
            }
        }

        return [
            'by_requirement' => $byRequirement,
            'unsatisfied' => $unsatisfied,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $designArtifact
     * @return array<string, mixed>
     */
    protected function makeRow(
        ?Project $project,
        ?BusinessObjective $objective,
        ?BusinessNeed $need,
        ?StakeholderNeed $stakeholderNeed,
        ?Feature $feature,
        ?FunctionalRequirement $functionalRequirement,
        ?array $designArtifact,
        ?string $gapType,
    ): array {
        $gaps = [];
        $scenarioCount = $feature !== null
            ? (int) ($feature->scenarios_count ?? 0)
            : 0;

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
        // Stakeholder need without FR or BDD feature packaging.
        if ($feature === null && $functionalRequirement === null && $stakeholderNeed !== null) {
            $gaps[] = 'missing_feature';
        }
        // Linked feature must have at least one scenario.
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
        if ($gapType === 'missing_satisfy') {
            $gaps[] = 'missing_satisfy';
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
            'design_artifact_flow_id' => $designArtifact['flow_id'] ?? null,
            'design_artifact_flow_title' => $designArtifact['flow_title'] ?? null,
            'design_artifact_code' => $designArtifact['code'] ?? null,
            'design_artifact_label' => $designArtifact['label'] ?? null,
            'design_artifact_type' => $designArtifact['type'] ?? null,
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
