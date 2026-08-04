<?php

namespace Tests\Unit;

use App\Models\Architecture;
use App\Services\C4MermaidGenerator;
use App\Services\GherkinFeatureAssembler;
use App\Services\ProjectExportService;
use App\Services\ProjectReadinessService;
use App\Services\StateDiagramMermaidGenerator;
use App\Services\SwimlaneMermaidGenerator;
use App\Services\TraceabilityMatrixService;
use Mockery;
use Tests\TestCase;

class ProjectExportArchitectureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_architecture_export_is_null_when_missing(): void
    {
        $service = $this->makeService();

        $this->assertNull($service->buildArchitectureExport(null));
    }

    public function test_architecture_export_is_null_when_empty_shell(): void
    {
        $architecture = new Architecture([
            'title' => 'Empty C4',
            'elements' => [],
            'relationships' => [],
            'layout' => [],
        ]);

        $service = $this->makeService();

        $this->assertNull($service->buildArchitectureExport($architecture));
    }

    public function test_architecture_export_includes_context_container_and_component_views(): void
    {
        $architecture = new Architecture([
            'title' => 'Banking C4',
            'elements' => [
                ['key' => 'customer', 'kind' => 'person', 'name' => 'Customer'],
                ['key' => 'banking', 'kind' => 'system', 'name' => 'Internet Banking'],
                ['key' => 'web', 'kind' => 'container', 'name' => 'Web App', 'parent_key' => 'banking'],
                ['key' => 'api', 'kind' => 'component', 'name' => 'API', 'parent_key' => 'web'],
            ],
            'relationships' => [
                ['from_key' => 'customer', 'to_key' => 'banking', 'label' => 'Uses'],
            ],
            'layout' => [],
        ]);

        $pack = $this->makeService()->buildArchitectureExport($architecture);

        $this->assertIsArray($pack);
        $this->assertSame($architecture, $pack['model']);
        $this->assertCount(3, $pack['views']);
        $this->assertSame('context', $pack['views'][0]['level']);
        $this->assertStringStartsWith('C4Context', trim($pack['views'][0]['mermaid']));
        $this->assertSame('container', $pack['views'][1]['level']);
        $this->assertStringContainsString('Internet Banking', $pack['views'][1]['title']);
        $this->assertStringStartsWith('C4Container', trim($pack['views'][1]['mermaid']));
        $this->assertSame('component', $pack['views'][2]['level']);
        $this->assertStringContainsString('Web App', $pack['views'][2]['title']);
        $this->assertStringStartsWith('C4Component', trim($pack['views'][2]['mermaid']));
    }

    public function test_process_state_models_partial_includes_architecture_partial(): void
    {
        $blade = file_get_contents(
            resource_path('views/pages/projects/babok/partials/process-state-models.blade.php')
        );

        $this->assertIsString($blade);
        $this->assertStringContainsString('architecture-c4', $blade);
        $this->assertStringContainsString('$hasArchitecture', $blade);
    }

    public function test_full_export_pack_includes_architecture_section_when_gated(): void
    {
        $blade = file_get_contents(
            resource_path('views/pages/projects/export.blade.php')
        );

        $this->assertIsString($blade);
        $this->assertStringContainsString('$hasArchitecture', $blade);
        $this->assertStringContainsString('section-architecture', $blade);
        $this->assertStringContainsString('architecture-c4', $blade);
        $this->assertStringContainsString("__('ui.architecture_c4')", $blade);
    }

    protected function makeService(): ProjectExportService
    {
        return new ProjectExportService(
            Mockery::mock(TraceabilityMatrixService::class),
            Mockery::mock(StateDiagramMermaidGenerator::class),
            Mockery::mock(SwimlaneMermaidGenerator::class),
            new C4MermaidGenerator(),
            Mockery::mock(ProjectReadinessService::class),
            Mockery::mock(GherkinFeatureAssembler::class),
        );
    }
}
