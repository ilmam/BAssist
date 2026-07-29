<?php

namespace App\Services;

use App\Models\Assumption;
use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\BusinessRule;
use App\Models\Constraint;
use App\Models\Feature;
use App\Models\Project;
use App\Models\ScopeItem;
use App\Models\StakeholderNeed;
use App\Models\StrategicBaseline;
use App\Support\AssumptionStatus;
use App\Support\EntityAccess;
use App\Support\StrategicBaselineStatus;

/**
 * Derived readiness / gap summary for a project (encourage, don't police).
 */
class ProjectReadinessService
{
    /**
     * @return array{
     *     total_gaps: int,
     *     items: list<array{key: string, label: string, count: int, severity: string, url: string|null}>
     * }
     */
    public function forProject(Project $project): array
    {
        $scopeQuery = [
            'workspace_id' => (int) $project->workspace_id,
            'project_id' => (int) $project->id,
        ];

        $items = [];

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
        }

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

            $count = BusinessNeed::query()
                ->where('project_id', $project->id)
                ->whereDoesntHave('stakeholderNeeds')
                ->count();
            $items[] = $this->item(
                key: 'needs_without_stories',
                label: __('ui.readiness_needs_without_stories'),
                count: $count,
                severity: 'warn',
                url: model_route('BusinessNeed', 'index').'?'.http_build_query($scopeQuery),
            );
        }

        if (entity_can('StakeholderNeed', EntityAccess::VIEW)) {
            $count = StakeholderNeed::query()
                ->where('project_id', $project->id)
                ->whereDoesntHave('businessNeeds')
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
                ->count();
            $items[] = $this->item(
                key: 'stories_without_features',
                label: __('ui.readiness_stories_without_features'),
                count: $count,
                severity: 'warn',
                url: model_route('StakeholderNeed', 'index').'?'.http_build_query($scopeQuery),
            );
        }

        if (entity_can('Feature', EntityAccess::VIEW)) {
            $count = Feature::query()
                ->where('project_id', $project->id)
                ->whereNull('stakeholder_need_id')
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
                url: route('guardrails.index', $scopeQuery),
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
                url: route('guardrails.index', $scopeQuery),
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
                url: route('guardrails.index', $scopeQuery),
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
                url: route('strategy.index', $scopeQuery),
            );
        }

        $gapItems = array_values(array_filter($items, fn (array $item) => $item['count'] > 0));

        return [
            'total_gaps' => array_sum(array_column($gapItems, 'count')),
            'items' => $gapItems,
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
}
