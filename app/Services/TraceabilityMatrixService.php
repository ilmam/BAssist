<?php

namespace App\Services;

use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\Feature;
use App\Models\Project;
use App\Models\StakeholderNeed;
use App\Models\Workspace;
use App\Support\ProjectContext;
use App\Support\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds a derived traceability matrix from FK / pivot links.
 * Chain: Objective ↔ Need ↔ Stakeholder Need → Feature → Scenarios
 * (Feature via Feature.stakeholder_need_id; scenarios_count > 0 required).
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
     *   summary: array{total: int, gaps: int, orphan_objectives: int, orphan_needs: int, orphan_stakeholder_needs: int, orphan_features: int, features_without_scenarios: int},
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

        $rows = collect()
            ->merge($this->rowsFromNeeds($projectId, $workspaceId))
            ->merge($this->orphanObjectiveRows($projectId, $workspaceId))
            ->merge($this->orphanStakeholderNeedRows($projectId, $workspaceId))
            ->merge($this->orphanFeatureRows($projectId, $workspaceId))
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
                'features_without_scenarios' => $rows->filter(
                    fn (array $r) => in_array('missing_scenarios', $r['gaps'], true)
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

                    if ($features === null || $features->isEmpty()) {
                        $rows[] = $this->makeRow(
                            project: $need->project,
                            objective: $objective,
                            need: $need,
                            stakeholderNeed: $stakeholderNeed,
                            feature: null,
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
                            gapType: $this->featureRowGapType($objective, $feature),
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
                    ->orderBy('number')
                    ->orderBy('title'),
            ])
            ->orderBy('number')
            ->orderBy('title')
            ->get();

        $rows = [];

        foreach ($stakeholderNeeds as $stakeholderNeed) {
            $features = $stakeholderNeed->features;
            if ($features->isEmpty()) {
                $rows[] = $this->makeRow(
                    project: $stakeholderNeed->project,
                    objective: null,
                    need: null,
                    stakeholderNeed: $stakeholderNeed,
                    feature: null,
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
                    gapType: 'orphan_stakeholder_need',
                );
            }
        }

        return $rows;
    }

    /**
     * Features with no Stakeholder Need FK (matrix gap).
     *
     * @return list<array<string, mixed>>
     */
    protected function orphanFeatureRows(?int $projectId, ?int $workspaceId): array
    {
        $features = $this->scopedQuery(Feature::query(), $projectId, $workspaceId)
            ->whereNull('stakeholder_need_id')
            ->withCount('scenarios')
            ->with('project:id,name,code')
            ->orderBy('number')
            ->orderBy('title')
            ->get();

        return $features->map(fn (Feature $feature) => $this->makeRow(
            project: $feature->project,
            objective: null,
            need: null,
            stakeholderNeed: null,
            feature: $feature,
            gapType: 'orphan_feature',
        ))->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeRow(
        ?Project $project,
        ?BusinessObjective $objective,
        ?BusinessNeed $need,
        ?StakeholderNeed $stakeholderNeed,
        ?Feature $feature,
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
        // Stakeholder need without a linked BDD feature (or orphan SN with no features).
        if ($feature === null && $stakeholderNeed !== null) {
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
