<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Active UI Theme
    |--------------------------------------------------------------------------
    |
    | Supported: metronic8, metronic9
    |
    */

    'theme' => env('UI_THEME', 'metronic9'),

    /*
    |--------------------------------------------------------------------------
    | Modal Actions (overlay routing)
    |--------------------------------------------------------------------------
    |
    | When true, DataTable actions open in the shared modal by default.
    | Modal URLs also work as shareable deep links: opening them directly
    | in the browser renders the same content as a full page.
    |
    */

    'modal_view' => env('UI_MODAL_VIEW', true),
    'modal_edit' => env('UI_MODAL_EDIT', true),
    'modal_create' => env('UI_MODAL_CREATE', true),
    'modal_quick_create' => env('UI_MODAL_QUICK_CREATE', true),

    /*
    |--------------------------------------------------------------------------
    | Form Controls
    |--------------------------------------------------------------------------
    |
    | select: default select rendering for Form/ListForm type "select".
    |   - kt     → Metronic KTSelect (class kt-select + data-kt-select)
    |   - native → plain select styled as kt-input
    |
    | Override per field with #[Form('select', 'Project', ktSelect: true|false)]
    | or type "kt-select" (always enhanced).
    |
    */

    'forms' => [
        'select' => env('UI_FORM_SELECT', 'kt'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Buttons
    |--------------------------------------------------------------------------
    |
    | Default size for <x-button> / ui_btn_classes() when size is omitted.
    | md matches Metronic default .kt-btn and .kt-input height (8.5 spacing).
    | Use sm only for toolbars, table chrome, and dense editors.
    |
    | Variants: primary, secondary, outline, ghost, destructive, mono
    |
    */

    'buttons' => [
        'size' => env('UI_BUTTON_SIZE', 'md'),
    ],

    /*
    |--------------------------------------------------------------------------
    | DataTables
    |--------------------------------------------------------------------------
    |
    | collapsed_actions: when true, row actions render as a single ⋮ menu
    | instead of inline icon buttons. Override per table via
    | <x-datatable collapsedActions="true" /> or options['collapsedActions'].
    |
    */

    'datatables' => [
        'collapsed_actions' => env('UI_DATATABLE_COLLAPSED_ACTIONS', false),
    ],

    'themes' => [
        'metronic8' => [
            'layout' => 'themes.metronic8.template',
            'asset_prefix' => 'themes/metronic8/assets',
        ],
        'metronic9' => [
            'layout' => 'themes.metronic9.template',
            'asset_prefix' => 'themes/metronic9/assets',
        ],
    ],

];
