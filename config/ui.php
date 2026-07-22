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
