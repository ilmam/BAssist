<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CRUD Overrides
    |--------------------------------------------------------------------------
    |
    | Routable entities are discovered when a model is marked #[RoutableAttribute]
    | and a matching repository exists. Entities without the attribute may still
    | use a repository internally but never receive HTTP routes.
    |
    | Use this file to override presentation settings or disable routing at
    | runtime without removing the attribute from the model.
    |
    */

    'exclude' => [
        // 'User',
    ],

    'models' => [
        'Category' => [
            'home' => true,
            'nav' => true,
            'nav_label' => 'Categories',
            'nav_icon' => 'category',
            'nav_icon_v8' => 'category',
        ],

        // 'LegacyThing' => ['disabled' => true],
    ],

];
