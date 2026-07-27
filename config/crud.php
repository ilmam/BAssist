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
    | Model key order in this file controls nav/home listing order.
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
        // 'Status' => [
        //     'nav' => true,
        //     'nav_label' => 'Statuses',
        //     'nav_icon' => 'category',
        //     'nav_icon_v8' => 'category',
        // ],

        // 'Priority' => [
        //     'nav' => true,
        //     'nav_label' => 'Priorities',
        //     'nav_icon' => 'category',
        //     'nav_icon_v8' => 'category',
        // ],

        'Workspace' => [
            'nav' => true,
            'nav_container' => true,
            'nav_label' => 'Workspaces',
            'nav_icon' => 'folder',
            'nav_icon_v8' => 'folder',
        ],

        'Project' => [
            'home' => true,
            'nav' => true,
            'nav_container' => true,
            'nav_label' => 'Projects',
            'nav_icon' => 'abstract-26',
            'nav_icon_v8' => 'abstract-26',
        ],

        'BusinessObjective' => [
            'home' => true,
            'nav' => true,
            'nav_label' => 'Business Objectives',
            'nav_icon' => 'category',
            'nav_icon_v8' => 'category',
        ],

        'BusinessNeed' => [
            'home' => true,
            'nav' => true,
            'nav_label' => 'Business Needs',
            'nav_icon' => 'category',
            'nav_icon_v8' => 'category',
        ],

        'Stakeholder' => [
            'home' => true,
            'nav' => true,
            'nav_label' => 'Stakeholders',
            'nav_icon' => 'category',
            'nav_icon_v8' => 'category',
        ],

        'StakeholderNeed' => [
            'home' => true,
            'nav' => true,
            'nav_label' => 'Stakeholder Needs',
            'nav_icon' => 'category',
            'nav_icon_v8' => 'category',
        ],

        'Feature' => [
            'home' => true,
            'nav' => true,
            'nav_label' => 'BDD Features',
            'nav_icon' => 'category',
            'nav_icon_v8' => 'category',
            'controller' => \App\Http\Controllers\FeatureController::class,
        ],

        // Routes kept for edit/delete; create/view UX is Feature-centric.
        'Scenario' => [
            'home' => false,
            'nav' => false,
            'nav_label' => 'Scenarios',
            'nav_icon' => 'category',
            'nav_icon_v8' => 'category',
            'controller' => \App\Http\Controllers\ScenarioController::class,
        ],

        'StateFlow' => [
            'home' => false,
            'nav' => false,
            'nav_label' => 'State Flows',
            'nav_icon' => 'abstract-39',
            'nav_icon_v8' => 'abstract-39',
        ],

        'SwimlaneFlow' => [
            'home' => false,
            'nav' => false,
            'nav_label' => 'Swimlane Flows',
            'nav_icon' => 'row-horizontal',
            'nav_icon_v8' => 'row-horizontal',
        ],

        // 'LegacyThing' => ['disabled' => true],
    ],

];
