<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectReadinessService;
use App\Support\EntityAccess;
use Illuminate\View\View;

class ProjectDashboardController extends Controller
{
    /**
     * @var list<array{model: string, count: string, label: string}>
     */
    protected const ARTIFACTS = [
        [
            'model' => 'BusinessObjective',
            'count' => 'business_objectives_count',
            'label' => 'business_objectives',
        ],
        [
            'model' => 'BusinessNeed',
            'count' => 'business_needs_count',
            'label' => 'business_needs',
        ],
        [
            'model' => 'Stakeholder',
            'count' => 'stakeholders_count',
            'label' => 'stakeholders',
        ],
        [
            'model' => 'StakeholderNeed',
            'count' => 'stakeholder_needs_count',
            'label' => 'stakeholder_needs',
        ],
        [
            'model' => 'Feature',
            'count' => 'features_count',
            'label' => 'features',
        ],
        [
            'model' => 'FunctionalRequirement',
            'count' => 'functional_requirements_count',
            'label' => 'functional_requirements',
        ],
        [
            'model' => 'ChangeRequest',
            'count' => 'change_requests_count',
            'label' => 'change_requests',
        ],
        [
            'model' => 'Risk',
            'count' => 'risks_count',
            'label' => 'risks',
        ],
        [
            'model' => 'Architecture',
            'count' => 'architecture_exists',
            'label' => 'architecture_c4',
        ],
        [
            'model' => 'StateFlow',
            'count' => 'state_flows_count',
            'label' => 'state_flows',
        ],
        [
            'model' => 'SwimlaneFlow',
            'count' => 'swimlane_flows_count',
            'label' => 'swimlane_flows',
        ],
        [
            'model' => 'Assumption',
            'count' => 'assumptions_count',
            'label' => 'assumptions',
        ],
        [
            'model' => 'Constraint',
            'count' => 'constraints_count',
            'label' => 'constraints',
        ],
        [
            'model' => 'BusinessRule',
            'count' => 'business_rules_count',
            'label' => 'business_rules',
        ],
        [
            'model' => 'StrategicBaseline',
            'count' => 'strategic_baseline_exists',
            'label' => 'strategic_baseline',
        ],
        [
            'model' => 'ScopeItem',
            'count' => 'scope_items_count',
            'label' => 'scope_items',
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
            'functionalRequirements',
            'changeRequests',
            'risks',
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
                'Feature', 'FunctionalRequirement' => route('solution_requirements.index', $scopeQuery),
                'ChangeRequest' => model_route('ChangeRequest', 'index').'?'.http_build_query($scopeQuery),
                default => model_route($artifact['model'], 'index').'?'.http_build_query($scopeQuery),
            };

            $counts[] = [
                'label' => __('ui.'.$artifact['label']),
                'count' => $countValue,
                'icon' => entity_icon($artifact['model']),
                'url' => $url,
            ];
        }

        $links = [];

        foreach (config('navigation.hierarchy.project_folders', []) as $folder) {
            if (! is_array($folder)) {
                continue;
            }

            foreach ($folder['children'] ?? [] as $child) {
                if (! is_array($child)) {
                    continue;
                }

                if (isset($child['entity'])) {
                    $entity = (string) $child['entity'];
                    if (! entity_can($entity, EntityAccess::VIEW)) {
                        continue;
                    }

                    $options = \App\Support\CrudEntityRegistry::all()[$entity] ?? [];
                    $links[] = [
                        'label' => $options['nav_label'] ?? $entity,
                        'url' => model_route($entity, 'index').'?'.http_build_query($scopeQuery),
                        'icon' => entity_icon($entity),
                        'group' => $folder['short'] ?? ($folder['label'] ?? null),
                    ];

                    continue;
                }

                if (! nav_item_is_visible($child)) {
                    continue;
                }

                $route = $child['route'] ?? null;
                if (! is_string($route) || $route === '') {
                    continue;
                }

                $url = match (true) {
                    $route === 'change_requests.index' => model_route('ChangeRequest', 'index').'?'.http_build_query($scopeQuery),
                    isset($child['route_project_param']) => route($route, [
                        (string) $child['route_project_param'] => $project->id,
                    ]),
                    default => route($route, $scopeQuery),
                };

                $links[] = [
                    'label' => $child['label'] ?? $route,
                    'url' => $url,
                    'icon' => $child['icon'] ?? entity_icon($this->surfaceKeyForRoute($route), 'element-11'),
                    'group' => $folder['short'] ?? ($folder['label'] ?? null),
                ];
            }
        }

        $links[] = [
            'label' => __('ui.babok_documents'),
            'url' => route('projects.babok.index', $project),
            'icon' => entity_icon('babok_documents'),
        ];

        $links[] = [
            'label' => __('ui.export_pack'),
            'url' => route('projects.export', $project),
            'icon' => entity_icon('export_pack'),
            'external' => true,
        ];

        return view('pages.projects.dashboard', [
            'project' => $project,
            'counts' => $counts,
            'links' => $links,
            'readiness' => $this->readiness->forProject($project),
        ]);
    }

    protected function surfaceKeyForRoute(string $route): string
    {
        return match ($route) {
            'solution_requirements.index' => 'solution_requirements',
            'diagrams.index' => 'diagrams',
            'change_requests.index' => 'change_requests',
            'traceability.index' => 'traceability',
            'acceptance-plan.index' => 'acceptance_plan',
            'strategic_baselines.for-project' => 'strategic_baseline',
            default => $route,
        };
    }
}
