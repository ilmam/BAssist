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
        // Sidebar project mark (BA logo); rendered as <img>, not KeenIcons.
        'project_icon_img' => 'images/ba-logo.png',

        /*
         | Project folders (order = BA journey).
         | Child keys:
         |   entity  — CRUD model leaf (route from registry)
         |   route   — hub / non-CRUD page
         |   entities — visibility gate (any VIEW permission)
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
                'children' => [
                    [
                        'entity' => 'BusinessNeed',
                    ],
                    [
                        'entity' => 'BusinessObjective',
                    ],
                    [
                        'entity' => 'Risk',
                    ],
                    [
                        'label' => 'Strategic Baseline',
                        'route' => 'strategic_baselines.for-project',
                        'route_project_param' => 'project',
                        'icon' => 'flag',
                        'icon_v8' => 'flag',
                        'entities' => ['StrategicBaseline'],
                    ],
                    [
                        'entity' => 'ScopeItem',
                    ],
                ],
            ],
            [
                'key' => 'radd',
                'label' => 'Requirements Modeling',
                'short' => 'Modeling',
                'babok' => 'KA 7 — Requirements Analysis & Design Definition',
                'purpose' => 'Actors → elicitation → rules & assumptions → solution requirements → data dictionary → diagrams.',
                'icon' => 'abstract-26',
                'icon_v8' => 'abstract-26',
                'children' => [
                    [
                        'entity' => 'Stakeholder',
                    ],
                    [
                        'entity' => 'StakeholderNeed',
                    ],
                    [
                        'label' => 'Rules & Assumptions',
                        'route' => 'guardrails.index',
                        'icon' => 'scroll',
                        'icon_v8' => 'scroll',
                        'entities' => ['Assumption', 'Constraint', 'BusinessRule'],
                    ],
                    [
                        'label' => 'Solution Requirements',
                        'route' => 'solution_requirements.index',
                        'icon' => 'subtitle',
                        'icon_v8' => 'subtitle',
                        'entities' => ['Feature', 'FunctionalRequirement', 'NonFunctionalRequirement'],
                    ],
                    [
                        'entity' => 'DataDictionary',
                    ],
                    [
                        // Keep as one hub until diagrams get a better home.
                        'label' => 'Diagrams',
                        'route' => 'diagrams.index',
                        'icon' => 'share',
                        'icon_v8' => 'share',
                        'entities' => ['Architecture', 'StateFlow', 'SwimlaneFlow'],
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
                'children' => [
                    [
                        'label' => 'Change Requests',
                        'route' => 'change_requests.index',
                        'icon' => 'arrow-mix',
                        'icon_v8' => 'arrow-mix',
                        'entities' => ['ChangeRequest'],
                    ],
                    [
                        'label' => 'Traceability',
                        'route' => 'traceability.index',
                        'icon' => 'fasten',
                        'icon_v8' => 'fasten',
                        'entities' => ['BusinessNeed', 'BusinessObjective', 'StakeholderNeed'],
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
                'children' => [
                    [
                        'label' => 'Acceptance Test',
                        'route' => 'acceptance-plan.index',
                        'icon' => 'check-squared',
                        'icon_v8' => 'check-squared',
                        'entities' => ['Feature', 'Scenario', 'FunctionalRequirement', 'NonFunctionalRequirement'],
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
