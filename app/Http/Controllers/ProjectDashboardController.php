<?php

namespace App\Http\Controllers;

use App\Models\Project;
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
            'model' => 'StateFlow',
            'count' => 'state_flows_count',
            'label' => 'state_flows',
            'icon' => 'abstract-39',
        ],
        [
            'model' => 'SwimlaneFlow',
            'count' => 'swimlane_flows_count',
            'label' => 'swimlane_flows',
            'icon' => 'abstract-44',
        ],
    ];

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
        ]);

        $scopeQuery = [
            'workspace_id' => (int) $project->workspace_id,
            'project_id' => (int) $project->id,
        ];

        $counts = [];
        foreach (self::ARTIFACTS as $artifact) {
            if (! entity_can($artifact['model'], EntityAccess::VIEW)) {
                continue;
            }

            $counts[] = [
                'label' => __('ui.'.$artifact['label']),
                'count' => (int) ($project->getAttribute($artifact['count']) ?? 0),
                'icon' => $artifact['icon'],
                'url' => model_route($artifact['model'], 'index').'?'.http_build_query($scopeQuery),
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
                'icon' => 'check-square',
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
        ]);
    }
}
