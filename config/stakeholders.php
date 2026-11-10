<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System-locked stakeholders (BABOK Ch2 generic roles)
    |--------------------------------------------------------------------------
    |
    | Seeded into every project on create. Locked (is_system): not deletable.
    | Projects may still add custom stakeholders (e.g. Product Owner) in operation.
    |
    */

    'system' => [
        ['key' => 'business_analyst', 'name' => 'Business Analyst', 'type' => 'business_analyst'],
        ['key' => 'customer', 'name' => 'Customer', 'type' => 'customer'],
        ['key' => 'domain_sme', 'name' => 'Domain Subject Matter Expert', 'type' => 'domain_sme'],
        ['key' => 'end_user', 'name' => 'End User', 'type' => 'end_user'],
        ['key' => 'implementation_sme', 'name' => 'Implementation Subject Matter Expert', 'type' => 'implementation_sme'],
        ['key' => 'operational_support', 'name' => 'Operational Support', 'type' => 'operational_support'],
        ['key' => 'project_manager', 'name' => 'Project Manager', 'type' => 'project_manager'],
        ['key' => 'regulator', 'name' => 'Regulator', 'type' => 'regulator'],
        ['key' => 'sponsor', 'name' => 'Sponsor', 'type' => 'sponsor'],
        ['key' => 'supplier', 'name' => 'Supplier', 'type' => 'supplier'],
        ['key' => 'tester', 'name' => 'Tester', 'type' => 'tester'],
    ],

    'statuses' => [
        'draft',
        'agreed',
        'deprecated',
    ],

];
