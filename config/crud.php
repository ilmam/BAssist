<?php

use App\Http\Controllers\ArchitectureController;
use App\Http\Controllers\ChangeRequestController;
use App\Http\Controllers\DataDictionaryController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\ScenarioController;
use App\Http\Controllers\ScreenController;
use App\Http\Controllers\StrategicBaselineController;
use App\Http\Controllers\SwimlaneFlowController;

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

        // Icons: config/entity_icons.php (overlaid onto nav_icon by CrudEntityRegistry).
        'BusinessNeed' => [
            'home' => true,
            'nav' => false,
            'nav_label' => 'Business Needs',
            'nav_icon' => 'electricity',
            'nav_icon_v8' => 'electricity',
        ],

        'BusinessObjective' => [
            'home' => true,
            'nav' => false,
            'nav_label' => 'Business Objectives',
            'nav_icon' => 'focus',
            'nav_icon_v8' => 'focus',
        ],

        'Stakeholder' => [
            'home' => true,
            'nav' => false,
            'nav_label' => 'Stakeholders',
            'nav_icon' => 'people',
            'nav_icon_v8' => 'people',
        ],

        'StakeholderNeed' => [
            'home' => true,
            'nav' => false,
            'nav_label' => 'Stakeholder Needs',
            'nav_icon' => 'message-text',
            'nav_icon_v8' => 'message-text',
        ],

        'Feature' => [
            'home' => true,
            'nav' => false,
            'nav_label' => 'BDD Features',
            'nav_icon' => 'category',
            'nav_icon_v8' => 'category',
            'controller' => FeatureController::class,
        ],

        'FunctionalRequirement' => [
            'home' => true,
            'nav' => false,
            'nav_label' => 'Functional Requirements',
            'nav_icon' => 'subtitle',
            'nav_icon_v8' => 'subtitle',
        ],

        'NonFunctionalRequirement' => [
            'home' => true,
            'nav' => false,
            'nav_label' => 'Non-Functional Requirements',
            'nav_icon' => 'chart-line',
            'nav_icon_v8' => 'chart-line',
        ],

        'ChangeRequest' => [
            'home' => true,
            'nav' => false,
            'nav_label' => 'Change Requests',
            'nav_icon' => 'arrow-mix',
            'nav_icon_v8' => 'arrow-mix',
            'controller' => ChangeRequestController::class,
        ],

        'Risk' => [
            'home' => true,
            'nav' => false,
            'nav_label' => 'Risk Assessment',
            'nav_icon' => 'shield-cross',
            'nav_icon_v8' => 'shield-cross',
        ],

        // Routes kept for edit/delete; create/view UX is Feature-centric.
        'Scenario' => [
            'home' => false,
            'nav' => false,
            'nav_label' => 'Scenarios',
            'nav_icon' => 'category',
            'nav_icon_v8' => 'category',
            'controller' => ScenarioController::class,
        ],

        'Architecture' => [
            'home' => false,
            'nav' => false,
            'nav_label' => 'Architecture (C4)',
            'nav_icon' => 'abstract-26',
            'nav_icon_v8' => 'abstract-26',
            'controller' => ArchitectureController::class,
            // C4 editor needs the full page; never open create/edit/view in a modal.
            'use_modals' => false,
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
            'controller' => SwimlaneFlowController::class,
        ],

        'DataDictionary' => [
            'home' => false,
            'nav' => false,
            'nav_label' => 'Data Dictionaries',
            'nav_icon' => 'tablet-text-down',
            'nav_icon_v8' => 'tablet-text-down',
            'controller' => DataDictionaryController::class,
            'use_modals' => false,
        ],

        'Assumption' => [
            'home' => false,
            'nav' => false,
            'nav_label' => 'Assumptions',
            'nav_icon' => 'question-2',
            'nav_icon_v8' => 'question-2',
        ],

        'Constraint' => [
            'home' => false,
            'nav' => false,
            'nav_label' => 'Constraints',
            'nav_icon' => 'lock-2',
            'nav_icon_v8' => 'lock-2',
        ],

        'BusinessRule' => [
            'home' => false,
            'nav' => false,
            'nav_label' => 'Business Rules',
            'nav_icon' => 'scroll',
            'nav_icon_v8' => 'scroll',
        ],

        'StrategicBaseline' => [
            'home' => false,
            'nav' => false,
            'nav_label' => 'Strategic Baseline',
            'nav_icon' => 'flag',
            'nav_icon_v8' => 'flag',
            'controller' => StrategicBaselineController::class,
            // Single document per project; full-page edit, never modal.
            'use_modals' => false,
        ],

        'ScopeItem' => [
            'home' => false,
            'nav' => false,
            'nav_label' => 'Scope Items',
            'nav_icon' => 'abstract-14',
            'nav_icon_v8' => 'abstract-14',
        ],

        // Design layer (docs/design-layer.md). Elements are edited from their screen's page.
        'Screen' => [
            'home' => false,
            'nav' => false,
            'nav_label' => 'UI Designs',
            'nav_icon' => 'screen',
            'nav_icon_v8' => 'screen',
            'controller' => ScreenController::class,
            // The details page is a full page (mockup + element rows); never open it in a modal.
            'use_modals' => false,
        ],

        'ScreenElement' => [
            'home' => false,
            'nav' => false,
            'nav_label' => 'Screen Elements',
            'nav_icon' => 'element-8',
            'nav_icon_v8' => 'element-8',
        ],

        // 'LegacyThing' => ['disabled' => true],
    ],

];
