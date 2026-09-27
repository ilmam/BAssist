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

    /*
    |--------------------------------------------------------------------------
    | Default tenant for users without one
    |--------------------------------------------------------------------------
    |
    | When true, a signed-in user with no tenant is provisioned on first
    | authentication using the mode above (shared → joins the default tenant,
    | personal → gets their own). When false, such users see no data.
    |
    */

    'auto_assign' => (bool) env('TENANCY_AUTO_ASSIGN', true),

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
