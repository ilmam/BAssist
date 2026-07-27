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
    | Workspace → Project → Artifact hierarchy
    |--------------------------------------------------------------------------
    |
    | Replaces the flat "Entities" accordion. Artifact models come from
    | config/crud.php (nav: true) excluding Workspace and Project containers.
    | Extra non-CRUD project links (e.g. Traceability) use project_artifacts.
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
        'project_artifacts' => [
            [
                'label' => 'Traceability',
                'route' => 'traceability.index',
                'icon' => 'abstract-26',
                'icon_v8' => 'abstract-26',
                'entities' => ['BusinessNeed', 'BusinessObjective', 'StakeholderNeed'],
            ],
            [
                'label' => 'Acceptance Test',
                'route' => 'acceptance-plan.index',
                'icon' => 'check-squared',
                'icon_v8' => 'check-squared',
                'entities' => ['Feature', 'Scenario'],
            ],
            [
                'label' => 'Diagrams',
                'route' => 'diagrams.index',
                'icon' => 'share',
                'icon_v8' => 'share',
                'entities' => ['StateFlow', 'SwimlaneFlow'],
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
