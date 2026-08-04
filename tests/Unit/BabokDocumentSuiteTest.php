<?php

namespace Tests\Unit;

use App\Services\BabokDocumentService;
use Tests\TestCase;

class BabokDocumentSuiteTest extends TestCase
{
    public function test_catalog_defines_three_baseline_packages(): void
    {
        $documents = config('babok_documents.documents');

        $this->assertCount(3, $documents);
        $this->assertEqualsCanonicalizing([
            'strategy-analysis',
            'requirements-design',
            'traceability-governance',
        ], array_keys($documents));
    }

    public function test_each_package_has_sections_and_partials(): void
    {
        $service = app(BabokDocumentService::class);

        foreach (array_keys(config('babok_documents.documents')) as $key) {
            $this->assertTrue($service->hasDocument($key), "Missing document [{$key}]");
            $meta = $service->documentMeta($key);
            $this->assertNotSame($meta['title'], __($meta['title']));
            $this->assertNotSame($meta['purpose'], __($meta['purpose']));
            $this->assertNotEmpty($meta['sections']);

            foreach ($meta['sections'] as $section) {
                $this->assertFileExists(
                    resource_path('views/pages/projects/babok/partials/'.$section['partial'].'.blade.php')
                );
            }
        }
    }

    public function test_packages_bundle_expected_section_structure(): void
    {
        $docs = config('babok_documents.documents');

        $this->assertSame(
            ['current-state-and-needs', 'future-state-and-objectives', 'risk-assessment', 'change-strategy-scope'],
            array_column($docs['strategy-analysis']['sections'], 'key')
        );
        $this->assertSame(
            ['stakeholder-requirements', 'solution-requirements', 'process-state-models', 'acceptance-criteria'],
            array_column($docs['requirements-design']['sections'], 'key')
        );
        $this->assertSame(
            ['stakeholder-engagement', 'governance', 'traceability-matrix'],
            array_column($docs['traceability-governance']['sections'], 'key')
        );
    }

    public function test_orphan_filtering_enabled_on_derived_sections(): void
    {
        $sections = collect(config('babok_documents.documents'))
            ->flatMap(fn ($doc) => $doc['sections'])
            ->keyBy('key');

        $this->assertTrue($sections['future-state-and-objectives']['filter_orphans']);
        $this->assertTrue($sections['stakeholder-requirements']['filter_orphans']);
        $this->assertTrue($sections['solution-requirements']['filter_orphans']);
        $this->assertTrue($sections['acceptance-criteria']['filter_orphans']);
        $this->assertFalse($sections['current-state-and-needs']['filter_orphans']);
        $this->assertFalse($sections['stakeholder-engagement']['filter_orphans']);
        $this->assertFalse($sections['traceability-matrix']['filter_orphans']);
    }

    public function test_export_partials_omit_doc_note_tips(): void
    {
        $partialsDir = resource_path('views/pages/projects/babok/partials');
        $files = glob($partialsDir.'/*.blade.php') ?: [];

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $blade = file_get_contents($file);
            $this->assertIsString($blade);
            $this->assertStringNotContainsString(
                'doc-note',
                $blade,
                'Export tip class doc-note must not appear in '.basename($file)
            );
        }
    }

    public function test_document_template_omits_per_section_babok_task_refs(): void
    {
        $blade = file_get_contents(
            resource_path('views/pages/projects/babok/document.blade.php')
        );

        $this->assertIsString($blade);
        $this->assertStringNotContainsString("\$section['babok']", $blade);
        $this->assertStringContainsString("\$document['babok']", $blade);
        $this->assertStringNotContainsString('doc-note', $blade);
    }

    public function test_traceability_matrix_uses_objective_first_lineage(): void
    {
        $blade = file_get_contents(
            resource_path('views/pages/projects/babok/partials/traceability-matrix.blade.php')
        );

        $this->assertIsString($blade);
        $objectivePos = strpos($blade, "__('ui.business_objective')");
        $needPos = strpos($blade, "__('ui.business_need')");
        $this->assertNotFalse($objectivePos);
        $this->assertNotFalse($needPos);
        $this->assertLessThan($needPos, $objectivePos);

        $this->assertNotSame(
            'ui.babok_doc_traceability_matrix_note',
            __('ui.babok_doc_traceability_matrix_note')
        );
        $this->assertStringContainsString(
            'Business Objective → Business Need',
            __('ui.babok_doc_traceability_matrix_note')
        );
        $this->assertStringContainsString(
            'Task 5.1',
            __('ui.babok_doc_traceability_matrix_note')
        );
    }

    public function test_guidance_notes_resolve_with_task_refs(): void
    {
        $keys = [
            'babok_step_current_state_needs_note' => 'Task 6.1',
            'babok_step_future_state_objectives_note' => 'Task 6.2',
            'babok_step_risk_assessment_note' => 'Task 6.3',
            'babok_step_change_strategy_scope_note' => 'Task 6.4',
            'babok_doc_stakeholder_engagement_note' => 'Task 3.2',
            'babok_doc_governance_note' => 'Tasks 3.3',
            'babok_doc_stakeholder_requirements_note' => 'Task 7.1',
            'babok_doc_solution_requirements_note' => 'Task 7.1',
            'babok_doc_process_state_models_note' => 'Task 7.4',
            'babok_doc_acceptance_criteria_note' => 'Task 7.2',
            'babok_doc_traceability_matrix_note' => 'Task 5.1',
        ];

        foreach ($keys as $key => $task) {
            $text = __('ui.'.$key);
            $this->assertNotSame('ui.'.$key, $text);
            $this->assertStringContainsString($task, $text);
        }
    }

    public function test_package_labels_cover_complete_proof_package(): void
    {
        $this->assertStringContainsString('Potential Value', __('ui.babok_step_future_state_objectives'));
        $this->assertStringContainsString('Risk Assessment', __('ui.babok_step_risk_assessment'));
        $this->assertStringContainsString('Step D', __('ui.babok_step_change_strategy_scope'));
        $this->assertStringContainsString('Solution Scope', __('ui.babok_step_change_strategy_scope'));
        $this->assertNotSame('ui.babok_doc_stakeholder_engagement', __('ui.babok_doc_stakeholder_engagement'));
        $this->assertNotSame('ui.babok_doc_governance', __('ui.babok_doc_governance'));
    }

    public function test_process_state_models_item_count_includes_architecture_views(): void
    {
        $service = app(\App\Services\BabokDocumentService::class);
        $method = new \ReflectionMethod($service, 'sectionItemCount');
        $method->setAccessible(true);

        $pack = [
            'state_flows' => [1, 2],
            'swimlane_flows' => [1],
            'architecture' => [
                'views' => [
                    ['level' => 'context'],
                    ['level' => 'container'],
                ],
            ],
        ];

        $project = new \App\Models\Project;

        $this->assertSame(
            5,
            $method->invoke($service, 'process-state-models', $pack, $project)
        );
    }
}
