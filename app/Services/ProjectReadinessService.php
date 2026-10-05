<?php

namespace App\Services;

use App\Models\Assumption;
use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\BusinessRule;
use App\Models\ChangeRequest;
use App\Models\Constraint;
use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\NonFunctionalRequirement;
use App\Models\Project;
use App\Models\Risk;
use App\Models\ScopeItem;
use App\Models\StakeholderNeed;
use App\Models\StrategicBaseline;
use App\Support\AssumptionStatus;
use App\Support\ChangeRequestStatus;
use App\Support\EntityAccess;
use App\Support\EntityPriority;
use App\Support\EntityStatus;
use App\Support\RiskImpact;
use App\Support\RiskLikelihood;
use App\Support\RiskResponse;
use App\Support\RiskStatus;
use App\Support\StrategicBaselineStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Derived readiness / gap summary for a project (encourage, don't police).
 */
class ProjectReadinessService
{
    /**
     * @return array{
     *     total_gaps: int,
     *     items: list<array{key: string, label: string, count: int, severity: string, url: string|null}>,
     *     severity: array{critical: int, warn: int, info: int},
     *     spine: list<array{key: string, label: string, ready: int, total: int, pct: int|null, url: string|null}>,
     *     score: int|null,
     *     folders: list<array<string, mixed>>
     * }
     */
    public function forProject(Project $project): array
    {
        $scopeQuery = [
            'workspace_id' => (int) $project->workspace_id,
            'project_id' => (int) $project->id,
        ];

        $items = [];

        if (entity_can('BusinessNeed', EntityAccess::VIEW)) {
            $count = BusinessNeed::query()
                ->where('project_id', $project->id)
                ->whereDoesntHave('businessObjectives')
                ->count();
            $items[] = $this->item(
                key: 'needs_without_objective',
                label: __('ui.readiness_needs_without_objective'),
                count: $count,
                severity: 'warn',
                url: route('traceability.index', $scopeQuery + ['orphans_only' => 1]),
            );

        }

        if (entity_can('BusinessObjective', EntityAccess::VIEW)) {
            $count = BusinessObjective::query()
                ->where('project_id', $project->id)
                ->whereDoesntHave('businessNeeds')
                ->count();
            $items[] = $this->item(
                key: 'orphan_objectives',
                label: __('ui.readiness_orphan_objectives'),
                count: $count,
                severity: 'warn',
                url: route('traceability.index', $scopeQuery + ['orphans_only' => 1]),
            );

            $count = BusinessObjective::query()
                ->where('project_id', $project->id)
                ->whereDoesntHave('stakeholderNeeds')
                ->count();
            $items[] = $this->item(
                key: 'objectives_without_stories',
                label: __('ui.readiness_objectives_without_stories'),
                count: $count,
                severity: 'warn',
                url: model_route('BusinessObjective', 'index').'?'.http_build_query($scopeQuery),
            );
        }

        if (entity_can('StakeholderNeed', EntityAccess::VIEW)) {
            $count = StakeholderNeed::query()
                ->where('project_id', $project->id)
                ->whereDoesntHave('businessObjectives')
                ->count();
            $items[] = $this->item(
                key: 'orphan_stories',
                label: __('ui.readiness_orphan_stories'),
                count: $count,
                severity: 'warn',
                url: route('traceability.index', $scopeQuery + ['orphans_only' => 1]),
            );

            $count = StakeholderNeed::query()
                ->where('project_id', $project->id)
                ->whereDoesntHave('features')
                ->whereDoesntHave('functionalRequirements')
                ->whereDoesntHave('nonFunctionalRequirements')
                ->whereDoesntHave('coveringScenarios')
                ->tap(fn ($query) => $this->excludeOutOfReleaseNeeds($query))
                ->count();
            $items[] = $this->item(
                key: 'stories_without_features',
                label: __('ui.readiness_stories_without_solution_packaging'),
                count: $count,
                severity: 'warn',
                url: route('solution_requirements.index', $scopeQuery),
            );
        }

        if (entity_can('ChangeRequest', EntityAccess::VIEW)) {
            $count = ChangeRequest::query()
                ->where('project_id', $project->id)
                ->whereIn('status', [ChangeRequestStatus::DRAFT, ChangeRequestStatus::UNDER_REVIEW])
                ->count();
            $items[] = $this->item(
                key: 'unconfirmed_change_requests',
                label: __('ui.readiness_unconfirmed_change_requests'),
                count: $count,
                severity: 'warn',
                url: model_route('ChangeRequest', 'index').'?'.http_build_query($scopeQuery),
            );

            $count = ChangeRequest::query()
                ->where('project_id', $project->id)
                ->whereNull('stakeholder_need_id')
                ->whereIn('status', ChangeRequestStatus::requiresStakeholderNeed())
                ->count();
            $items[] = $this->item(
                key: 'crs_without_stakeholder_need',
                label: __('ui.readiness_crs_without_stakeholder_need'),
                count: $count,
                severity: 'critical',
                url: model_route('ChangeRequest', 'index').'?'.http_build_query($scopeQuery),
            );
        }

        if (entity_can('FunctionalRequirement', EntityAccess::VIEW)) {
            $count = FunctionalRequirement::query()
                ->where('project_id', $project->id)
                ->whereNull('stakeholder_need_id')
                ->whereNull('change_request_id')
                ->count();
            $items[] = $this->item(
                key: 'orphan_functional_requirements',
                label: __('ui.readiness_orphan_functional_requirements'),
                count: $count,
                severity: 'warn',
                url: route('traceability.index', $scopeQuery + ['orphans_only' => 1]),
            );

            $count = FunctionalRequirement::query()
                ->where('project_id', $project->id)
                ->where(function ($query) {
                    $query->whereNull('acceptance_criteria')
                        ->orWhere('acceptance_criteria', '');
                })
                ->count();
            $items[] = $this->item(
                key: 'frs_without_acceptance',
                label: __('ui.readiness_frs_without_acceptance'),
                count: $count,
                severity: 'info',
                url: route('solution_requirements.index', $scopeQuery),
            );
        }

        if (entity_can('NonFunctionalRequirement', EntityAccess::VIEW)) {
            $count = NonFunctionalRequirement::query()
                ->where('project_id', $project->id)
                ->whereNull('stakeholder_need_id')
                ->whereNull('change_request_id')
                ->count();
            $items[] = $this->item(
                key: 'orphan_non_functional_requirements',
                label: __('ui.readiness_orphan_non_functional_requirements'),
                count: $count,
                severity: 'warn',
                url: route('traceability.index', $scopeQuery + ['orphans_only' => 1]),
            );

            $count = NonFunctionalRequirement::query()
                ->where('project_id', $project->id)
                ->where(function ($query) {
                    $query->whereNull('acceptance_criteria')
                        ->orWhere('acceptance_criteria', '');
                })
                ->count();
            $items[] = $this->item(
                key: 'nfrs_without_acceptance',
                label: __('ui.readiness_nfrs_without_acceptance'),
                count: $count,
                severity: 'info',
                url: route('solution_requirements.index', $scopeQuery),
            );
        }

        $needRevisionId = EntityStatus::id(EntityStatus::NEED_REVISION);
        if ($needRevisionId !== null && (
            entity_can('FunctionalRequirement', EntityAccess::VIEW)
            || entity_can('NonFunctionalRequirement', EntityAccess::VIEW)
            || entity_can('Feature', EntityAccess::VIEW)
        )) {
            $frCount = entity_can('FunctionalRequirement', EntityAccess::VIEW)
                ? FunctionalRequirement::query()
                    ->where('project_id', $project->id)
                    ->where('status_id', $needRevisionId)
                    ->count()
                : 0;
            $nfrCount = entity_can('NonFunctionalRequirement', EntityAccess::VIEW)
                ? NonFunctionalRequirement::query()
                    ->where('project_id', $project->id)
                    ->where('status_id', $needRevisionId)
                    ->count()
                : 0;
            $feCount = entity_can('Feature', EntityAccess::VIEW)
                ? Feature::query()
                    ->where('project_id', $project->id)
                    ->where('status_id', $needRevisionId)
                    ->count()
                : 0;
            $items[] = $this->item(
                key: 'need_revision_packaging',
                label: __('ui.readiness_need_revision_packaging'),
                count: $frCount + $nfrCount + $feCount,
                severity: 'critical',
                url: route('solution_requirements.index', $scopeQuery),
            );
        }

        if (entity_can('Feature', EntityAccess::VIEW)) {
            $count = Feature::query()
                ->where('project_id', $project->id)
                ->whereNull('stakeholder_need_id')
                ->whereNull('change_request_id')
                ->count();
            $items[] = $this->item(
                key: 'orphan_features',
                label: __('ui.readiness_orphan_features'),
                count: $count,
                severity: 'warn',
                url: route('traceability.index', $scopeQuery + ['orphans_only' => 1]),
            );

            $count = Feature::query()
                ->where('project_id', $project->id)
                ->whereDoesntHave('scenarios')
                ->count();
            $items[] = $this->item(
                key: 'features_without_scenarios',
                label: __('ui.readiness_features_without_scenarios'),
                count: $count,
                severity: 'critical',
                url: route('traceability.index', $scopeQuery + ['orphans_only' => 1]),
            );
        }

        if (entity_can('Assumption', EntityAccess::VIEW)) {
            $count = Assumption::query()
                ->where('project_id', $project->id)
                ->where('status', AssumptionStatus::OPEN)
                ->count();
            $items[] = $this->item(
                key: 'open_assumptions',
                label: __('ui.readiness_open_assumptions'),
                count: $count,
                severity: 'critical',
                url: model_route('Assumption', 'index').'?'.http_build_query($scopeQuery),
            );
        }

        // Unresolved discussion: review remarks and findings raised during delivery.
        // One line per status, because each waits on someone different.
        $threadCounts = app(CommentService::class)->statusCounts($project);
        foreach ([
            'comments_awaiting_answer' => [\App\Support\CommentStatus::OPEN, 'warn'],
            'comments_awaiting_implementation' => [\App\Support\CommentStatus::ANSWERED, 'warn'],
            'comments_awaiting_verification' => [\App\Support\CommentStatus::IMPLEMENTED, 'info'],
        ] as $key => [$status, $severity]) {
            $items[] = $this->item(
                key: $key,
                label: __('ui.readiness_'.$key),
                count: $threadCounts[$status],
                severity: $severity,
                url: route('projects.comments', ['project' => $project, 'status' => $status]),
            );
        }

        if (entity_can('Risk', EntityAccess::VIEW)) {
            $risksUrl = model_route('Risk', 'index').'?'.http_build_query($scopeQuery);

            $criticalRisks = Risk::query()
                ->where('project_id', $project->id)
                ->where('likelihood', RiskLikelihood::HIGH)
                ->where('impact', RiskImpact::HIGH);

            $activeCritical = (clone $criticalRisks)
                ->whereIn('status', RiskStatus::active())
                ->count();
            $items[] = $this->item(
                key: 'active_critical_risks',
                label: __('ui.readiness_active_critical_risks'),
                count: $activeCritical,
                severity: 'critical',
                url: $risksUrl,
            );

            $criticalWithoutResponse = (clone $criticalRisks)
                ->where(function ($query): void {
                    $query->whereNull('response')->orWhere('response', '');
                })
                ->count();
            $items[] = $this->item(
                key: 'critical_risks_without_response',
                label: __('ui.readiness_critical_risks_without_response'),
                count: $criticalWithoutResponse,
                severity: 'warn',
                url: $risksUrl,
            );

            $criticalWithoutTreatment = (clone $criticalRisks)
                ->where(function ($query): void {
                    $query->whereNull('treatment')->orWhere('treatment', '');
                })
                ->where(function ($query): void {
                    $query->whereNull('response')
                        ->orWhere('response', '!=', RiskResponse::ACCEPT);
                })
                ->count();
            $items[] = $this->item(
                key: 'critical_risks_without_treatment',
                label: __('ui.readiness_critical_risks_without_treatment'),
                count: $criticalWithoutTreatment,
                severity: 'warn',
                url: $risksUrl,
            );

            $acceptedWithoutRationale = Risk::query()
                ->where('project_id', $project->id)
                ->where('response', RiskResponse::ACCEPT)
                ->where(function ($q): void {
                    $q->whereNull('treatment')->orWhere('treatment', '');
                })
                ->count();
            $items[] = $this->item(
                key: 'accepted_risks_without_rationale',
                label: __('ui.readiness_accepted_risks_without_rationale'),
                count: $acceptedWithoutRationale,
                severity: 'critical',
                url: $risksUrl,
            );

            $hasRisks = Risk::query()->where('project_id', $project->id)->exists();
            $items[] = $this->item(
                key: 'risks_captured',
                label: __('ui.readiness_no_risks'),
                count: $hasRisks ? 0 : 1,
                severity: 'info',
                url: $risksUrl,
            );
        }

        if (entity_can('Constraint', EntityAccess::VIEW)) {
            $hasConstraints = Constraint::query()
                ->where('project_id', $project->id)
                ->exists();
            $items[] = $this->item(
                key: 'constraints_captured',
                label: __('ui.readiness_no_constraints'),
                count: $hasConstraints ? 0 : 1,
                severity: 'info',
                url: model_route('Constraint', 'index').'?'.http_build_query($scopeQuery),
            );
        }

        if (entity_can('BusinessRule', EntityAccess::VIEW)) {
            $hasRules = BusinessRule::query()
                ->where('project_id', $project->id)
                ->exists();
            $items[] = $this->item(
                key: 'rules_captured',
                label: __('ui.readiness_no_business_rules'),
                count: $hasRules ? 0 : 1,
                severity: 'info',
                url: model_route('BusinessRule', 'index').'?'.http_build_query($scopeQuery),
            );
        }

        if (entity_can('StrategicBaseline', EntityAccess::VIEW)) {
            $baseline = StrategicBaseline::query()
                ->where('project_id', $project->id)
                ->first();
            $baselineUrl = route('strategic_baselines.for-project', $project->id);

            if ($baseline === null || ! $baseline->hasStrategyContent()) {
                $items[] = $this->item(
                    key: 'baseline_missing',
                    label: __('ui.readiness_no_strategic_baseline'),
                    count: 1,
                    severity: 'info',
                    url: $baselineUrl,
                );
            } elseif ($baseline->status === StrategicBaselineStatus::DRAFT) {
                $items[] = $this->item(
                    key: 'baseline_draft',
                    label: __('ui.readiness_strategic_baseline_draft'),
                    count: 1,
                    severity: 'warn',
                    url: $baselineUrl,
                );
            }
        }

        if (entity_can('ScopeItem', EntityAccess::VIEW)) {
            $hasScopeItems = ScopeItem::query()
                ->where('project_id', $project->id)
                ->exists();
            $items[] = $this->item(
                key: 'scope_items_captured',
                label: __('ui.readiness_no_scope_items'),
                count: $hasScopeItems ? 0 : 1,
                severity: 'info',
                url: model_route('ScopeItem', 'index').'?'.http_build_query($scopeQuery),
            );
        }

        $items = array_map(fn (array $item) => $this->decorate($item), $items);
        $gapItems = array_values(array_filter($items, fn (array $item) => $item['count'] > 0));
        $spine = $this->spineCoverage($project, $scopeQuery);

        $severity = ['critical' => 0, 'warn' => 0, 'info' => 0];
        foreach ($gapItems as $item) {
            $tone = $item['severity'];
            if (isset($severity[$tone])) {
                $severity[$tone] += (int) $item['count'];
            }
        }

        return [
            'total_gaps' => array_sum(array_column($gapItems, 'count')),
            'items' => $gapItems,
            'severity' => $severity,
            'spine' => $spine,
            'score' => $this->coverageScore($spine),
            'folders' => $this->folderHealth($items),
        ];
    }

    /**
     * Readiness check key → BABOK project folder (config navigation.hierarchy.project_folders).
     *
     * @var array<string, string>
     */
    protected const FOLDER_FOR_CHECK = [
        'needs_without_objective' => 'strategy',
        'orphan_objectives' => 'strategy',
        'objectives_without_stories' => 'strategy',
        'active_critical_risks' => 'strategy',
        'critical_risks_without_response' => 'strategy',
        'critical_risks_without_treatment' => 'strategy',
        'accepted_risks_without_rationale' => 'strategy',
        'risks_captured' => 'strategy',
        'baseline_missing' => 'strategy',
        'baseline_draft' => 'strategy',
        'scope_items_captured' => 'strategy',
        'orphan_stories' => 'radd',
        'stories_without_features' => 'radd',
        'orphan_functional_requirements' => 'radd',
        'frs_without_acceptance' => 'radd',
        'orphan_non_functional_requirements' => 'radd',
        'nfrs_without_acceptance' => 'radd',
        'need_revision_packaging' => 'radd',
        'orphan_features' => 'radd',
        'open_assumptions' => 'radd',
        'constraints_captured' => 'radd',
        'rules_captured' => 'radd',
        'comments_awaiting_answer' => 'governance',
        'comments_awaiting_implementation' => 'governance',
        'comments_awaiting_verification' => 'governance',
        'unconfirmed_change_requests' => 'governance',
        'crs_without_stakeholder_need' => 'governance',
        'features_without_scenarios' => 'evaluation',
    ];

    /**
     * "Nothing captured yet" checks → entity whose create modal fixes the gap.
     *
     * @var array<string, string>
     */
    protected const CREATE_FIX_FOR_CHECK = [
        'risks_captured' => 'Risk',
        'constraints_captured' => 'Constraint',
        'rules_captured' => 'BusinessRule',
        'scope_items_captured' => 'ScopeItem',
    ];

    /**
     * Attach folder + optional one-click "fix" action to a readiness check.
     *
     * @param  array{key: string, label: string, count: int, severity: string, url: string|null}  $item
     * @return array<string, mixed>
     */
    protected function decorate(array $item): array
    {
        $item['folder'] = self::FOLDER_FOR_CHECK[$item['key']] ?? 'other';
        $item['fix_modal'] = null;

        $entity = self::CREATE_FIX_FOR_CHECK[$item['key']] ?? null;
        if ($entity !== null && entity_can($entity, EntityAccess::CREATE)) {
            $item['fix_modal'] = model_modal_path($entity, 'create');
        }

        return $item;
    }

    /**
     * Per-folder health: share of applicable checks that currently pass.
     *
     * @param  list<array<string, mixed>>  $items  All checks (passing and failing).
     * @return list<array{key: string, label: string, short: string, babok: string|null, icon: string, checks: int, passing: int, gaps: int, critical: int, pct: int|null}>
     */
    protected function folderHealth(array $items): array
    {
        $folders = [];

        foreach (config('navigation.hierarchy.project_folders', []) as $folder) {
            if (! is_array($folder) || ! isset($folder['key'])) {
                continue;
            }

            $key = (string) $folder['key'];
            $checks = array_values(array_filter($items, fn (array $item) => $item['folder'] === $key));
            $failing = array_values(array_filter($checks, fn (array $item) => $item['count'] > 0));
            $total = count($checks);
            $passing = $total - count($failing);

            $folders[] = [
                'key' => $key,
                'label' => (string) ($folder['label'] ?? $key),
                'short' => (string) ($folder['short'] ?? ($folder['label'] ?? $key)),
                'babok' => isset($folder['babok']) ? (string) $folder['babok'] : null,
                'icon' => (string) ($folder['icon'] ?? 'folder'),
                'checks' => $total,
                'passing' => $passing,
                'gaps' => (int) array_sum(array_column($failing, 'count')),
                'critical' => count(array_filter($failing, fn (array $item) => $item['severity'] === 'critical')),
                'pct' => $total > 0 ? (int) round(100 * $passing / $total) : null,
            ];
        }

        return $folders;
    }

    /**
     * @param  array{workspace_id: int, project_id: int}  $scopeQuery
     * @return list<array{key: string, label: string, ready: int, total: int, pct: int|null, url: string|null}>
     */
    protected function spineCoverage(Project $project, array $scopeQuery): array
    {
        $stages = [];
        $projectId = (int) $project->id;
        $qs = http_build_query($scopeQuery);

        if (entity_can('BusinessNeed', EntityAccess::VIEW)) {
            $total = BusinessNeed::query()->where('project_id', $projectId)->count();
            $ready = BusinessNeed::query()
                ->where('project_id', $projectId)
                ->whereHas('businessObjectives')
                ->count();
            $stages[] = $this->stage(
                'needs',
                __('ui.readiness_spine_needs'),
                $ready,
                $total,
                model_route('BusinessNeed', 'index').'?'.$qs,
            );
        }

        if (entity_can('BusinessObjective', EntityAccess::VIEW)) {
            $total = BusinessObjective::query()->where('project_id', $projectId)->count();
            $ready = BusinessObjective::query()
                ->where('project_id', $projectId)
                ->whereHas('businessNeeds')
                ->count();
            $stages[] = $this->stage(
                'objectives',
                __('ui.readiness_spine_objectives'),
                $ready,
                $total,
                model_route('BusinessObjective', 'index').'?'.$qs,
            );
        }

        if (entity_can('StakeholderNeed', EntityAccess::VIEW)) {
            $total = StakeholderNeed::query()->where('project_id', $projectId)->count();
            $ready = StakeholderNeed::query()
                ->where('project_id', $projectId)
                ->whereHas('businessObjectives')
                ->count();
            $stages[] = $this->stage(
                'stories',
                __('ui.readiness_spine_stories'),
                $ready,
                $total,
                model_route('StakeholderNeed', 'index').'?'.$qs,
            );

            $packaged = StakeholderNeed::query()
                ->where('project_id', $projectId)
                ->where(function ($query): void {
                    $query->whereHas('features')
                        ->orWhereHas('functionalRequirements')
                        ->orWhereHas('nonFunctionalRequirements')
                        ->orWhereHas('coveringScenarios');
                    $this->orOutOfReleaseNeeds($query);
                })
                ->count();
            $stages[] = $this->stage(
                'packaging',
                __('ui.readiness_spine_packaging'),
                $packaged,
                $total,
                route('solution_requirements.index', $scopeQuery),
            );
        }

        if (entity_can('Feature', EntityAccess::VIEW)) {
            $total = Feature::query()->where('project_id', $projectId)->count();
            $ready = Feature::query()
                ->where('project_id', $projectId)
                ->whereHas('scenarios')
                ->count();
            $stages[] = $this->stage(
                'scenarios',
                __('ui.readiness_spine_scenarios'),
                $ready,
                $total,
                model_route('Feature', 'index').'?'.$qs,
            );
        }

        return $stages;
    }

    /**
     * @param  list<array{ready: int, total: int}>  $stages
     */
    protected function coverageScore(array $stages): ?int
    {
        $scored = array_values(array_filter($stages, fn (array $stage) => $stage['total'] > 0));
        if ($scored === []) {
            return null;
        }

        $sum = 0.0;
        foreach ($scored as $stage) {
            $sum += $stage['ready'] / $stage['total'];
        }

        return (int) round(100 * $sum / count($scored));
    }

    /**
     * @return array{key: string, label: string, ready: int, total: int, pct: int|null, url: string|null}
     */
    protected function stage(string $key, string $label, int $ready, int $total, ?string $url): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'ready' => $ready,
            'total' => $total,
            'pct' => $total > 0 ? (int) round(100 * $ready / $total) : null,
            'url' => $url,
        ];
    }

    /**
     * @return array{key: string, label: string, count: int, severity: string, url: string|null}
     */
    protected function item(
        string $key,
        string $label,
        int $count,
        string $severity,
        ?string $url,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'count' => $count,
            'severity' => $severity,
            'url' => $url,
        ];
    }

    /**
     * Won't / Deprecated needs stay on the spine; they are not current-release packaging gaps.
     */
    protected function excludeOutOfReleaseNeeds(Builder $query): void
    {
        $wontId = EntityPriority::id(EntityPriority::WONT);
        $deprecatedId = EntityStatus::id(EntityStatus::DEPRECATED);

        if ($wontId !== null) {
            $query->where(function (Builder $inner) use ($wontId): void {
                $inner->whereNull('priority_id')->orWhere('priority_id', '!=', $wontId);
            });
        }

        if ($deprecatedId !== null) {
            $query->where(function (Builder $inner) use ($deprecatedId): void {
                $inner->whereNull('status_id')->orWhere('status_id', '!=', $deprecatedId);
            });
        }
    }

    /**
     * Treat Won't / Deprecated as packaged for the current-release spine.
     */
    protected function orOutOfReleaseNeeds(Builder $query): void
    {
        $wontId = EntityPriority::id(EntityPriority::WONT);
        $deprecatedId = EntityStatus::id(EntityStatus::DEPRECATED);

        if ($wontId !== null) {
            $query->orWhere('priority_id', $wontId);
        }

        if ($deprecatedId !== null) {
            $query->orWhere('status_id', $deprecatedId);
        }
    }
}
