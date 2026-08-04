<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Navigation
    |--------------------------------------------------------------------------
    |
    | Single source of truth for sidebar/header links.
    | Each theme renders these items with its own markup.
    |
    */

    'items' => [
        [
            'label' => 'Dashboard',
            'route' => 'theme.test',
            'icon' => 'element-11',
            'icon_v8' => 'element-11',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Workspace → Project → BABOK folders
    |--------------------------------------------------------------------------
    |
    | Under each project, artifacts are grouped into collapsible folders that
    | mirror the pre-approval / delivery / governance / evaluation journey.
    | Folders guide; they never lock access (iterative BA).
    |
    */
    'hierarchy' => [
        'label' => 'Workspaces',
        'icon' => 'folder',
        'icon_v8' => 'folder',
        'workspace_icon' => 'folder',
        'workspace_icon_v8' => 'folder',
        'project_icon' => 'abstract-26',
        'project_icon_v8' => 'abstract-26',
        'all_workspaces_label' => 'All Workspaces',
        'all_projects_label' => 'All Projects',

        // Temporarily hide BABOK folder progress badges in the sidebar.
        // Set true to re-enable (NavFolderProgress + blade markup remain in place).
        'show_folder_badges' => false,

        /*
         | Project folders (order = BA journey).
         | Child keys:
         |   entity  — CRUD model leaf (route from registry)
         |   route   — hub / non-CRUD page
         |   entities — visibility gate (any VIEW permission)
         |   progress — how the folder badge evaluates this leaf
         */
        'project_folders' => [
            [
                'key' => 'strategy',
                'label' => 'Strategy & Alignment',
                'short' => 'Strategy',
                'babok' => 'KA 6 — Strategy Analysis',
                'purpose' => 'Establishes why we are doing this and sets the baseline.',
                'icon' => 'compass',
                'icon_v8' => 'flag',
                'badge_tone' => 'strategy',
                'children' => [
                    [
                        'entity' => 'BusinessObjective',
                        'progress' => 'entity_agreed',
                    ],
                    [
                        'entity' => 'BusinessNeed',
                        'progress' => 'entity_agreed',
                    ],
                    [
                        'entity' => 'Risk',
                        'progress' => 'entity_present',
                    ],
                    [
                        'label' => 'Strategic Baseline',
                        'route' => 'strategic_baselines.for-project',
                        'route_project_param' => 'project',
                        'icon' => 'flag',
                        'icon_v8' => 'flag',
                        'entities' => ['StrategicBaseline'],
                        'progress' => 'strategic_baseline',
                    ],
                    [
                        'entity' => 'ScopeItem',
                        'progress' => 'entity_present',
                    ],
                ],
            ],
            [
                'key' => 'radd',
                'label' => 'Requirements & Design',
                'short' => 'RADD',
                'babok' => 'KA 7 — Requirements Analysis & Design Definition',
                'purpose' => 'Specifies what the solution looks like, who needs it, and the rules governing it.',
                'icon' => 'abstract-26',
                'icon_v8' => 'abstract-26',
                'badge_tone' => 'radd',
                'children' => [
                    [
                        'entity' => 'Stakeholder',
                        'progress' => 'entity_present',
                    ],
                    [
                        'entity' => 'StakeholderNeed',
                        'progress' => 'entity_agreed',
                    ],
                    [
                        'label' => 'Solution Requirements',
                        'route' => 'solution_requirements.index',
                        'icon' => 'note-2',
                        'icon_v8' => 'note-2',
                        'entities' => ['Feature', 'FunctionalRequirement'],
                        'progress' => 'solution_hub',
                    ],
                    [
                        'entity' => 'Assumption',
                        'progress' => 'entity_present',
                    ],
                    [
                        'entity' => 'Constraint',
                        'progress' => 'entity_present',
                    ],
                    [
                        'entity' => 'BusinessRule',
                        'progress' => 'entity_present',
                    ],
                    [
                        // Keep as one hub until diagrams get a better home.
                        'label' => 'Diagrams',
                        'route' => 'diagrams.index',
                        'icon' => 'share',
                        'icon_v8' => 'share',
                        'entities' => ['Architecture', 'StateFlow', 'SwimlaneFlow'],
                        'progress' => 'diagrams_hub',
                    ],
                ],
            ],
            [
                'key' => 'governance',
                'label' => 'Governance & Lifecycle',
                'short' => 'Governance',
                'babok' => 'KA 5 & KA 3 — Lifecycle / Planning & Monitoring',
                'purpose' => 'Tracks changes, impact, approvals, and structural lineage.',
                'icon' => 'arrow-mix',
                'icon_v8' => 'arrow-mix',
                'badge_tone' => 'governance',
                'children' => [
                    [
                        'label' => 'Change Requests',
                        'route' => 'change_requests.index',
                        'icon' => 'arrow-mix',
                        'icon_v8' => 'arrow-mix',
                        'entities' => ['ChangeRequest'],
                        'progress' => 'change_requests_hub',
                    ],
                    [
                        'label' => 'Traceability',
                        'route' => 'traceability.index',
                        'icon' => 'abstract-39',
                        'icon_v8' => 'abstract-39',
                        'entities' => ['BusinessNeed', 'BusinessObjective', 'StakeholderNeed'],
                        'progress' => 'traceability_hub',
                    ],
                ],
            ],
            [
                'key' => 'evaluation',
                'label' => 'Evaluation & Acceptance',
                'short' => 'Acceptance',
                'babok' => 'KA 8 — Solution Evaluation',
                'purpose' => 'Verifies the solution meets quality standards and delivers business value.',
                'icon' => 'check-squared',
                'icon_v8' => 'check-squared',
                'badge_tone' => 'evaluation',
                'children' => [
                    [
                        'label' => 'Acceptance Test',
                        'route' => 'acceptance-plan.index',
                        'icon' => 'check-squared',
                        'icon_v8' => 'check-squared',
                        'entities' => ['Feature', 'Scenario', 'FunctionalRequirement'],
                        'progress' => 'acceptance_hub',
                    ],
                ],
            ],
        ],
    ],

    'entities' => [
        'label' => 'Entities',
        'icon' => 'element-plus',
        'icon_v8' => 'element-plus',
    ],

    'administration' => [
        'label' => 'Administration',
        'icon' => 'setting-2',
        'icon_v8' => 'setting-2',
        'super_admin_only' => true,
        'children' => [
            [
                'label' => 'Roles',
                'route' => 'admin.roles.index',
            ],
            [
                'label' => 'Users',
                'route' => 'admin.users.index',
            ],
        ],
    ],

];
