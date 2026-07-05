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
        [
            'label' => 'Categories',
            'route' => 'categories.index',
            'icon' => 'category',
            'icon_v8' => 'category',
        ],
    ],

];
