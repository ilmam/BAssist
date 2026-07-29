<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectReadinessService;
use App\Support\EntityAccess;
use Illuminate\View\View;

class ProjectDashboardController extends Controller
{
    /**
     * @var list<array{model: string, count: string, label: string, icon: string}>
     */
    protected const ARTIFACTS = [
        [
            'model' => 'BusinessObjective',
            'count' => 'business_objectives_count',
            'label' => 'business_objectives',
            'icon' => 'chart-line-up-2',
        ],
        [
            'model' => 'BusinessNeed',
            'count' => 'business_needs_count',
            'label' => 'business_needs',
            'icon' => 'abstract-26',
        ],
        [
            'model' => 'Stakeholder',
            'count' => 'stakeholders_count',
            'label' => 'stakeholders',
            'icon' => 'people',
        ],
        [
            'model' => 'StakeholderNeed',
            'count' => 'stakeholder_needs_count',
            'label' => 'stakeholder_needs',
            'icon' => 'questionnaire-tablet',
        ],
        [
            'model' => 'Feature',
            'count' => 'features_count',
            'label' => 'features',
            'icon' => 'abstract-26',
        ],
        [
            'model' => 'Architecture',
            'count' => 'architecture_exists',
            'label' => 'architecture_c4',
            'icon' => 'abstract-26',
        ],
        [
            'model' => 'StateFlow',
            'count' => 'state_flows_count',
            'label' => 'state_flows',
            'icon' => 'abstract-39',
        ],
        [
            'model' => 'SwimlaneFlow',
            'count' => 'swimlane_flows_count',
            'label' => 'swimlane_flows',
            'icon' => 'row-horizontal',
        ],
        [
            'model' => 'Assumption',
            'count' => 'assumptions_count',
            'label' => 'assumptions',
            'icon' => 'questionnaire-tablet',
        ],
        [
            'model' => 'Constraint',
            'count' => 'constraints_count',
            'label' => 'constraints',
            'icon' => 'shield-tick',
        ],
        [
            'model' => 'BusinessRule',
            'count' => 'business_rules_count',
            'label' => 'business_rules',
            'icon' => 'book',
        ],
        [
            'model' => 'StrategicBaseline',
            'count' => 'strategic_baseline_exists',
            'label' => 'strategic_baseline',
            'icon' => 'flag',
        ],
        [
            'model' => 'ScopeItem',
            'count' => 'scope_items_count',
            'label' => 'scope_items',
            'icon' => 'abstract-14',
        ],
    ];

    public function __construct(protected ProjectReadinessService $readiness)
    {
    }

    public function show(Project $project): View
    {
        EntityAccess::authorize(auth()->user(), 'Project', EntityAccess::VIEW);

        $tenantId = auth()->user()?->tenant_id;
        $project->loadMissing(['workspace', 'status']);

        if ($tenantId !== null && (int) $project->workspace?->tenant_id !== (int) $tenantId) {
            abort(404);
        }

        $project->loadCount([
            'businessObjectives',
            'businessNeeds',
            'stakeholders',
            'stakeholderNeeds',
            'features',
            'stateFlows',
            'swimlaneFlows',
            'assumptions',
            'constraints',
            'businessRules',
            'scopeItems',
        ]);
        $project->loadExists(['architecture', 'strategicBaseline']);

        $scopeQuery = [
            'workspace_id' => (int) $project->workspace_id,
            'project_id' => (int) $project->id,
        ];

        $counts = [];
        foreach (self::ARTIFACTS as $artifact) {
            if (! entity_can($artifact['model'], EntityAccess::VIEW)) {
                continue;
            }

            $countValue = match ($artifact['count']) {
                'architecture_exists', 'strategic_baseline_exists' => ((bool) $project->getAttribute($artifact['count']) ? 1 : 0),
                default => (int) ($project->getAttribute($artifact['count']) ?? 0),
            };

            $url = match ($artifact['model']) {
                'Architecture' => route('architectures.for-project', $project->id),
                'StrategicBaseline' => route('strategic_baselines.for-project', $project->id),
                'Assumption', 'Constraint', 'BusinessRule' => route('guardrails.index', $scopeQuery),
                'ScopeItem' => route('strategy.index', $scopeQuery),
                default => model_route($artifact['model'], 'index').'?'.http_build_query($scopeQuery),
            };

            $counts[] = [
                'label' => __('ui.'.$artifact['label']),
                'count' => $countValue,
                'icon' => $artifact['icon'],
                'url' => $url,
            ];
        }

        $links = [];

        foreach ($counts as $count) {
            $links[] = [
                'label' => $count['label'],
                'url' => $count['url'],
                'icon' => $count['icon'],
            ];
        }

        if (nav_item_is_visible([
            'entities' => ['BusinessNeed', 'BusinessObjective', 'StakeholderNeed'],
            'route' => 'traceability.index',
        ])) {
            $links[] = [
                'label' => __('ui.traceability'),
                'url' => route('traceability.index', $scopeQuery),
                'icon' => 'abstract-26',
            ];
        }

        if (nav_item_is_visible([
            'entities' => ['Feature', 'Scenario'],
            'route' => 'acceptance-plan.index',
        ])) {
            $links[] = [
                'label' => __('ui.acceptance_plan'),
                'url' => route('acceptance-plan.index', $scopeQuery),
                'icon' => 'check-squared',
            ];
        }

        if (nav_item_is_visible([
            'entities' => ['Architecture', 'StateFlow', 'SwimlaneFlow'],
            'route' => 'diagrams.index',
        ])) {
            $links[] = [
                'label' => __('ui.diagrams'),
                'url' => route('diagrams.index', $scopeQuery),
                'icon' => 'share',
            ];
        }

        if (nav_item_is_visible([
            'entities' => ['Assumption', 'Constraint', 'BusinessRule'],
            'route' => 'guardrails.index',
        ])) {
            $links[] = [
                'label' => __('ui.guardrails'),
                'url' => route('guardrails.index', $scopeQuery),
                'icon' => 'shield-tick',
            ];
        }

        if (nav_item_is_visible([
            'entities' => ['StrategicBaseline', 'ScopeItem'],
            'route' => 'strategy.index',
        ])) {
            $links[] = [
                'label' => __('ui.strategy'),
                'url' => route('strategy.index', $scopeQuery),
                'icon' => 'flag',
            ];
        }

        $links[] = [
            'label' => __('ui.export_pack'),
            'url' => route('projects.export', $project),
            'icon' => 'file-down',
            'external' => true,
        ];

        return view('pages.projects.dashboard', [
            'project' => $project,
            'counts' => $counts,
            'links' => $links,
            'readiness' => $this->readiness->forProject($project),
        ]);
    }
}
