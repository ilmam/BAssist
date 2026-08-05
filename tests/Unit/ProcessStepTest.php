<?php

namespace Tests\Unit;

use App\Data\FeatureData;
use App\Data\FunctionalRequirementData;
use App\Models\SwimlaneFlowStep;
use App\Services\SwimlaneMermaidGenerator;
use App\Support\EntityFormBuilder;
use Tests\TestCase;

class ProcessStepTest extends TestCase
{
    public function test_entity_number_prefix_is_ps(): void
    {
        $method = new \ReflectionMethod(SwimlaneFlowStep::class, 'entityNumberPrefix');

        $this->assertSame(SwimlaneMermaidGenerator::STEP_CODE_PREFIX, $method->invoke(null));
    }

    public function test_fr_and_feature_forms_include_process_step(): void
    {
        $frFields = (new EntityFormBuilder)->fields(FunctionalRequirementData::class);
        $featureFields = (new EntityFormBuilder)->fields(FeatureData::class);

        $this->assertSame('select', $frFields['swimlane_flow_step_id']['type'] ?? null);
        $this->assertSame('select', $featureFields['swimlane_flow_step_id']['type'] ?? null);
        $this->assertContains('nullable', FunctionalRequirementData::rules()['swimlane_flow_step_id']);
        $this->assertContains('nullable', FeatureData::rules()['swimlane_flow_step_id']);
        $this->assertArrayHasKey('', $frFields['swimlane_flow_step_id']['list'] ?? []);
        $this->assertArrayHasKey('', $featureFields['swimlane_flow_step_id']['list'] ?? []);
    }

    public function test_matrix_service_exposes_process_step_gap_counters(): void
    {
        $service = file_get_contents(dirname(__DIR__, 2).'/app/Services/TraceabilityMatrixService.php');

        $this->assertIsString($service);
        $this->assertStringContainsString('countSwimlaneFlowStepsWithoutNeed', $service);
        $this->assertStringContainsString('countUncoveredSwimlaneFlowSteps', $service);
        $this->assertStringContainsString('swimlane_flow_step_id', $service);
        $this->assertStringNotContainsString('designStepsIndex', $service);
    }

    public function test_migration_promotes_elements_and_relinks_satisfy(): void
    {
        $migration = file_get_contents(
            dirname(__DIR__, 2).'/database/migrations/2026_08_04_153000_create_process_steps_and_relink.php'
        );

        $this->assertIsString($migration);
        $this->assertStringContainsString("Schema::create('swimlane_flow_steps'", $migration);
        $this->assertStringContainsString('swimlane_flow_step_id', $migration);
        $this->assertStringContainsString('stakeholder_need_id', $migration);
        $this->assertStringContainsString('migrateElementsToRows', $migration);
        $this->assertStringContainsString("update(['elements' => null])", $migration);
    }

    public function test_readiness_uses_process_step_gap_keys(): void
    {
        $service = file_get_contents(dirname(__DIR__, 2).'/app/Services/ProjectReadinessService.php');

        $this->assertIsString($service);
        $this->assertStringContainsString("key: 'process_steps_without_need'", $service);
        $this->assertStringContainsString("key: 'uncovered_process_steps'", $service);
        $this->assertStringContainsString('countSwimlaneFlowStepsWithoutNeed', $service);
        $this->assertStringContainsString('countUncoveredSwimlaneFlowSteps', $service);
    }

    public function test_swimlane_repository_syncs_rows_not_json(): void
    {
        $repo = file_get_contents(dirname(__DIR__, 2).'/app/Repositories/SwimlaneFlowRepository.php');

        $this->assertIsString($repo);
        $this->assertStringContainsString('syncSwimlaneFlowSteps', $repo);
        $this->assertStringContainsString("\$data['elements'] = null", $repo);
        $this->assertStringContainsString('elementsForEditor', $repo);
    }
}
