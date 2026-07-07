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
    | Per-model page views resolve automatically when a blade file exists:
    |   pages/{resource}/list.blade.php    → overrides pages/generic/list.blade.php
    |   pages/{resource}/form.blade.php
    |   pages/{resource}/details.blade.php
    | where {resource} is the plural snake resource name (e.g. categories).
    | No config entry is required for conventional overrides.
    |
    | Optional: set views.{action} below only when the view path does not
    | follow that convention (e.g. a shared or non-standard blade).
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
