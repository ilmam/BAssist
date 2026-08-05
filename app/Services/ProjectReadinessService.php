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
use App\Models\Project;
use App\Models\Risk;
use App\Models\ScopeItem;
use App\Models\StakeholderNeed;
use App\Models\StrategicBaseline;
use App\Support\AssumptionStatus;
use App\Support\ChangeRequestStatus;
use App\Support\EntityAccess;
use App\Support\EntityStatus;
use App\Support\RiskImpact;
use App\Support\RiskLikelihood;
use App\Support\RiskResponse;
use App\Support\RiskStatus;
use App\Support\StrategicBaselineStatus;

/**
 * Derived readiness / gap summary for a project (encourage, don't police).
 */
class ProjectReadinessService
{
    public function __construct(
        protected TraceabilityMatrixService $traceability,
    ) {
    }

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
                ->whereDoesntHave('functionalRequirements')
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

        $needRevisionId = EntityStatus::id(EntityStatus::NEED_REVISION);
        if ($needRevisionId !== null && (
            entity_can('FunctionalRequirement', EntityAccess::VIEW) || entity_can('Feature', EntityAccess::VIEW)
        )) {
            $frCount = entity_can('FunctionalRequirement', EntityAccess::VIEW)
                ? FunctionalRequirement::query()
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
                count: $frCount + $feCount,
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

        if (entity_can('SwimlaneFlow', EntityAccess::VIEW)) {
            $withoutNeed = $this->traceability->countSwimlaneFlowStepsWithoutNeed(
                (int) $project->id,
                (int) $project->workspace_id,
            );
            $items[] = $this->item(
                key: 'process_steps_without_need',
                label: __('ui.readiness_process_steps_without_need'),
                count: $withoutNeed,
                severity: 'warn',
                url: route('traceability.index', $scopeQuery + ['orphans_only' => 1]),
            );

            $uncovered = $this->traceability->countUncoveredSwimlaneFlowSteps(
                (int) $project->id,
                (int) $project->workspace_id,
            );
            $items[] = $this->item(
                key: 'uncovered_process_steps',
                label: __('ui.readiness_uncovered_process_steps'),
                count: $uncovered,
                severity: 'warn',
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
