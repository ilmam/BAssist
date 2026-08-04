<?php

return [

    /*
    |--------------------------------------------------------------------------
    | BA Concept Guide booklet
    |--------------------------------------------------------------------------
    |
    | Ordered journey steps for the global help booklet. At runtime only steps
    | whose markdown guide exists (HelpRegistry::exists) are shown.
    |
    */

    'title_key' => 'ui.ba_guide',

    'steps' => [
        ['key' => 'business_objectives', 'label' => 'Business Objectives'],
        ['key' => 'business_needs', 'label' => 'Business Needs'],
        ['key' => 'risks', 'label' => 'Risk Assessment'],
        ['key' => 'stakeholders', 'label' => 'Stakeholders'],
        ['key' => 'stakeholder_needs', 'label' => 'Stakeholder Needs'],
        ['key' => 'assumptions', 'label' => 'Assumptions'],
        ['key' => 'constraints', 'label' => 'Constraints'],
        ['key' => 'business_rules', 'label' => 'Business Rules'],
        ['key' => 'solution_requirements', 'label' => 'Solution Requirements'],
        ['key' => 'functional_requirements', 'label' => 'Functional Requirements'],
        ['key' => 'features', 'label' => 'Features (BDD)'],
        ['key' => 'traceability', 'label' => 'Traceability'],
        ['key' => 'acceptance_plan', 'label' => 'Acceptance Plan'],
    ],

];
