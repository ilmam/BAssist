<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Entity & surface icons (Metronic 9 Keenicons)
    |--------------------------------------------------------------------------
    |
    | Single source of truth for spine / hub icon slugs used by sidebar nav,
    | project dashboard, list child-link columns, and hub pages.
    |
    | Values are Keenicon names without the "ki-" / "ki-filled" prefix
    | (as expected by <x-button icon="..."> and menu blades).
    |
    | Prefer reading via entity_icon($key). CrudEntityRegistry overlays
    | models.* onto each entity's nav_icon / nav_icon_v8.
    |
    */

    'default' => 'element-11',

    'models' => [
        'Workspace' => 'folder',
        'Project' => 'abstract-26',

        // Strategy & Alignment
        'BusinessObjective' => 'focus',          // target / crosshair
        'BusinessNeed' => 'electricity',         // closest to lightbulb (no bulb glyph)
        'Risk' => 'shield-cross',                // warning / risk attention
        'StrategicBaseline' => 'flag',
        'ScopeItem' => 'abstract-14',            // stacked layers

        // Requirements Modeling
        'Stakeholder' => 'people',
        'StakeholderNeed' => 'message-text',     // speech bubble
        'Feature' => 'category',
        'Scenario' => 'category',
        'FunctionalRequirement' => 'subtitle',   // technical / system document
        'NonFunctionalRequirement' => 'chart-line', // QoS / measurable attributes
        'Assumption' => 'question-2',
        'Constraint' => 'lock-2',
        'BusinessRule' => 'scroll',              // rules / governance (book reserved for BABOK)

        // Diagrams (entity-level; hub uses surfaces.diagrams)
        'Architecture' => 'abstract-26',
        'StateFlow' => 'abstract-39',
        'SwimlaneFlow' => 'row-horizontal',

        // Governance
        'ChangeRequest' => 'arrow-mix',
    ],

    /*
    | Hub pages and non-CRUD surfaces (keys are stable app identifiers).
    */
    'surfaces' => [
        'guardrails' => 'scroll',                // rules / assumptions / constraints hub
        'solution_requirements' => 'subtitle',
        'diagrams' => 'share',                   // connected-node / flow diagram
        'change_requests' => 'arrow-mix',
        'traceability' => 'fasten',              // chain-link style
        'acceptance_plan' => 'check-squared',
        'babok_documents' => 'book',
        'export_pack' => 'file-down',
        'strategic_baseline' => 'flag',
    ],

];
