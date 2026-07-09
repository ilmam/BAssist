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
        ],
    ],

];
