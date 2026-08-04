<?php

/**
 * Pre-approval baseline packages (BABOK-aligned).
 *
 * Package 1 Strategy ("Why") · Package 2 Requirements ("What") · Package 3 Traceability & Governance ("Proof").
 * The full project export pack remains the comprehensive dump of all artifacts.
 */
return [
    'documents' => [
        'strategy-analysis' => [
            'title' => 'ui.babok_package_strategy',
            'babok' => 'BABOK Chapter 6 — Strategy Analysis',
            'purpose' => 'ui.babok_package_strategy_help',
            'sections' => [
                [
                    'key' => 'current-state-and-needs',
                    'heading' => 'ui.babok_step_current_state_needs',
                    'babok' => 'Task 6.1 Analyze Current State',
                    'partial' => 'current-state-and-needs',
                    'filter_orphans' => false,
                ],
                [
                    'key' => 'future-state-and-objectives',
                    'heading' => 'ui.babok_step_future_state_objectives',
                    'babok' => 'Task 6.2 Define Future State',
                    'partial' => 'future-state-and-objectives',
                    'filter_orphans' => true,
                ],
                [
                    'key' => 'risk-assessment',
                    'heading' => 'ui.babok_step_risk_assessment',
                    'babok' => 'Task 6.3 Assess Risks',
                    'partial' => 'risk-assessment',
                    'filter_orphans' => false,
                ],
                [
                    'key' => 'change-strategy-scope',
                    'heading' => 'ui.babok_step_change_strategy_scope',
                    'babok' => 'Task 6.4 Define Change Strategy',
                    'partial' => 'change-strategy-scope',
                    'filter_orphans' => false,
                ],
            ],
        ],
        'requirements-design' => [
            'title' => 'ui.babok_package_requirements',
            'babok' => 'BABOK Chapter 7 — Requirements Analysis & Design Definition',
            'purpose' => 'ui.babok_package_requirements_help',
            'sections' => [
                [
                    'key' => 'stakeholder-requirements',
                    'heading' => 'ui.babok_doc_stakeholder_requirements',
                    'babok' => 'Task 7.1 Specify and Model Requirements',
                    'partial' => 'stakeholder-requirements',
                    'filter_orphans' => true,
                ],
                [
                    'key' => 'solution-requirements',
                    'heading' => 'ui.babok_doc_solution_requirements',
                    'babok' => 'Task 7.1 Specify and Model Requirements',
                    'partial' => 'solution-requirements',
                    'filter_orphans' => true,
                ],
                [
                    'key' => 'process-state-models',
                    'heading' => 'ui.babok_doc_process_state_models',
                    'babok' => 'Task 7.4 Define Design Options · Techniques 10.35 & 10.44',
                    'partial' => 'process-state-models',
                    'filter_orphans' => false,
                ],
                [
                    'key' => 'acceptance-criteria',
                    'heading' => 'ui.babok_doc_acceptance_criteria',
                    'babok' => 'Task 7.2 Verify Requirements · Technique 10.6',
                    'partial' => 'acceptance-criteria',
                    'filter_orphans' => true,
                ],
            ],
        ],
        'traceability-governance' => [
            'title' => 'ui.babok_package_proof',
            'babok' => 'BABOK Chapters 3 & 5 — Planning, Monitoring & Traceability',
            'purpose' => 'ui.babok_package_proof_help',
            'sections' => [
                [
                    'key' => 'stakeholder-engagement',
                    'heading' => 'ui.babok_doc_stakeholder_engagement',
                    'babok' => 'Task 3.2 Stakeholder Engagement Approach',
                    'partial' => 'stakeholder-engagement',
                    'filter_orphans' => false,
                ],
                [
                    'key' => 'governance',
                    'heading' => 'ui.babok_doc_governance',
                    'babok' => 'Tasks 3.3 & 3.4 Governance / Information Management',
                    'partial' => 'governance',
                    'filter_orphans' => false,
                ],
                [
                    'key' => 'traceability-matrix',
                    'heading' => 'ui.babok_doc_traceability_matrix',
                    'babok' => 'Task 5.1 Trace Requirements',
                    'partial' => 'traceability-matrix',
                    'filter_orphans' => false,
                ],
            ],
        ],
    ],
];
