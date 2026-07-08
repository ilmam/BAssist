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
    | Modal fragments follow the same pattern under pages/modals/:
    |   pages/modals/view.blade.php, form.blade.php, delete.blade.php
    | Per-model modal overrides: pages/{resource}/modals/{action}.blade.php
    | Optional config escape hatch: modals.{action} on the model entry.
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

        'Country' => [
            'controller' => \App\Http\Controllers\CountryController::class,
            'api_controller' => \App\Http\Controllers\Api\CountryController::class,
            'nav' => true,
            'nav_label' => 'Countries',
            'nav_icon' => 'category',
            'nav_icon_v8' => 'category',
        ],

        // 'LegacyThing' => ['disabled' => true],
    ],

];
