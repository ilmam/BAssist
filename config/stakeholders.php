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
        ['key' => 'business_analyst', 'name' => 'Business Analyst', 'type' => 'role'],
        ['key' => 'customer', 'name' => 'Customer', 'type' => 'role'],
        ['key' => 'domain_sme', 'name' => 'Domain Subject Matter Expert', 'type' => 'role'],
        ['key' => 'end_user', 'name' => 'End User', 'type' => 'role'],
        ['key' => 'implementation_sme', 'name' => 'Implementation Subject Matter Expert', 'type' => 'role'],
        ['key' => 'operational_support', 'name' => 'Operational Support', 'type' => 'role'],
        ['key' => 'project_manager', 'name' => 'Project Manager', 'type' => 'role'],
        ['key' => 'regulator', 'name' => 'Regulator', 'type' => 'role'],
        ['key' => 'sponsor', 'name' => 'Sponsor', 'type' => 'role'],
        ['key' => 'supplier', 'name' => 'Supplier', 'type' => 'role'],
        ['key' => 'tester', 'name' => 'Tester', 'type' => 'role'],
    ],

    'statuses' => [
        'draft',
        'agreed',
        'deprecated',
    ],

];
