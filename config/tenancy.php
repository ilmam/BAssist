<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenancy mode
    |--------------------------------------------------------------------------
    |
    | personal — each new user gets their own tenant + default workspace.
    | shared   — new users join the seeded shared tenant/workspace (internal org).
    |            Suppresses personal-tenant provisioning; Tenant stays out of nav.
    |
    */

    'mode' => env('TENANCY_MODE', 'shared'),

    'shared' => [
        'tenant_slug' => env('SHARED_TENANT_SLUG', 'internal'),
        'tenant_name' => env('SHARED_TENANT_NAME', 'Internal Organization'),
        'workspace_slug' => env('SHARED_WORKSPACE_SLUG', 'default'),
        'workspace_name' => env('SHARED_WORKSPACE_NAME', 'Default Workspace'),
    ],

    'personal' => [
        'workspace_name' => 'Default Workspace',
        'workspace_slug' => 'default',
    ],

];
