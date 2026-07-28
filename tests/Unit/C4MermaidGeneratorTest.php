<?php

namespace Tests\Unit;

use App\Services\C4MermaidGenerator;
use App\Services\StructurizrExporter;
use PHPUnit\Framework\TestCase;

class C4MermaidGeneratorTest extends TestCase
{
    public function test_context_diagram_with_labeled_relationship_and_style(): void
    {
        $generator = new C4MermaidGenerator();

        $elements = [
            ['key' => 'customer', 'kind' => 'person', 'name' => 'Customer', 'description' => 'A user'],
            ['key' => 'banking', 'kind' => 'system', 'name' => 'Internet Banking', 'description' => 'Core system'],
            ['key' => 'mainframe', 'kind' => 'system', 'name' => 'Mainframe', 'external' => true, 'description' => 'Legacy'],
        ];
        $relationships = [
            ['from_key' => 'customer', 'to_key' => 'banking', 'label' => 'Uses', 'technology' => 'HTTPS'],
            ['from_key' => 'banking', 'to_key' => 'mainframe', 'label' => 'Gets account info'],
        ];
        $elements[1]['style'] = ['bg_color' => '#1168bd', 'font_color' => '#ffffff'];

        $mermaid = $generator->toContext($elements, $relationships);

        $this->assertStringStartsWith("C4Context\n", $mermaid);
        $this->assertStringContainsString('Person(customer, "Customer", "A user")', $mermaid);
        $this->assertStringContainsString('System(banking, "Internet Banking", "Core system")', $mermaid);
        $this->assertStringContainsString('System_Ext(mainframe, "Mainframe", "Legacy")', $mermaid);
        $this->assertStringContainsString('Rel(customer, banking, "Uses", "HTTPS")', $mermaid);
        $this->assertStringContainsString('Rel(banking, mainframe, "Gets account info")', $mermaid);
        $this->assertStringContainsString('UpdateElementStyle(banking, $bgColor="#1168bd", $fontColor="#ffffff")', $mermaid);
        $this->assertStringContainsString('UpdateLayoutConfig($c4ShapeInRow="4", $c4BoundaryInRow="2")', $mermaid);
    }

    public function test_context_groups_render_as_boundary(): void
    {
        $generator = new C4MermaidGenerator();

        $elements = [
            ['key' => 'internal', 'kind' => 'group', 'name' => 'Internal'],
            ['key' => 'banking', 'kind' => 'system', 'name' => 'Internet Banking', 'parent_key' => 'internal'],
            ['key' => 'customer', 'kind' => 'person', 'name' => 'Customer', 'parent_key' => 'internal'],
            ['key' => 'guest', 'kind' => 'person', 'name' => 'Guest'],
        ];
        $relationships = [
            ['from_key' => 'customer', 'to_key' => 'banking', 'label' => 'Uses'],
        ];

        $mermaid = $generator->toContext($elements, $relationships);

        $this->assertStringContainsString('Boundary(internal, "Internal") {', $mermaid);
        $this->assertStringContainsString('System(banking, "Internet Banking")', $mermaid);
        $this->assertStringContainsString('Person(customer, "Customer")', $mermaid);
        $this->assertStringContainsString('Person(guest, "Guest")', $mermaid);
        $this->assertStringContainsString('Rel(customer, banking, "Uses")', $mermaid);

        // Guest is ungrouped (outside the boundary block).
        $boundaryPos = strpos($mermaid, 'Boundary(internal');
        $guestPos = strpos($mermaid, 'Person(guest');
        $this->assertNotFalse($boundaryPos);
        $this->assertNotFalse($guestPos);
        $this->assertLessThan($boundaryPos, $guestPos);
    }

    public function test_normalizer_allows_system_and_person_under_group(): void
    {
        $normalizer = new \App\Services\C4ArchitectureNormalizer();
        $rows = $normalizer->normalizeElements([
            ['key' => 'g1', 'kind' => 'group', 'name' => 'Core'],
            ['key' => 'app', 'kind' => 'system', 'name' => 'App', 'parent_key' => 'g1'],
            ['key' => 'user', 'kind' => 'person', 'name' => 'User', 'parent_key' => 'g1'],
            ['key' => 'web', 'kind' => 'container', 'name' => 'Web', 'parent_key' => 'app'],
            ['key' => 'bad', 'kind' => 'system', 'name' => 'Bad Parent', 'parent_key' => 'app'],
        ]);
        $byKey = array_column($rows, null, 'key');

        $this->assertSame('g1', $byKey['app']['parent_key']);
        $this->assertSame('g1', $byKey['user']['parent_key']);
        $this->assertSame('app', $byKey['web']['parent_key']);
        $this->assertNull($byKey['bad']['parent_key']);
        $this->assertNull($byKey['g1']['parent_key']);
    }

    public function test_container_and_component_levels(): void
    {
        $generator = new C4MermaidGenerator();

        $elements = [
            ['key' => 'banking', 'kind' => 'system', 'name' => 'Internet Banking'],
            ['key' => 'web', 'kind' => 'container', 'name' => 'Web App', 'parent_key' => 'banking', 'technology' => 'JS'],
            ['key' => 'api', 'kind' => 'container', 'name' => 'API', 'parent_key' => 'banking', 'technology' => 'Java'],
            ['key' => 'signin', 'kind' => 'component', 'name' => 'Sign In', 'parent_key' => 'api', 'technology' => 'Spring'],
        ];
        $relationships = [
            ['from_key' => 'web', 'to_key' => 'api', 'label' => 'Calls'],
            ['from_key' => 'signin', 'to_key' => 'web', 'label' => 'Redirects'],
        ];

        $container = $generator->toContainer($elements, $relationships, 'banking');
        $this->assertStringStartsWith("C4Container\n", $container);
        $this->assertStringContainsString('System_Boundary(bankingBoundary', $container);
        $this->assertStringContainsString('Container(web, "Web App", "JS")', $container);
        $this->assertStringContainsString('Rel(web, api, "Calls")', $container);
        $this->assertStringNotContainsString('Rel(web, banking', $container);

        $component = $generator->toComponent($elements, $relationships, 'api');
        $this->assertStringStartsWith("C4Component\n", $component);
        $this->assertStringContainsString('Container_Boundary(apiBoundary', $component);
        $this->assertStringContainsString('Component(signin, "Sign In", "Spring")', $component);
        $this->assertStringContainsString('Rel(signin, web, "Redirects")', $component);
    }

    public function test_database_and_queue_forms(): void
    {
        $generator = new C4MermaidGenerator();

        $elements = [
            ['key' => 'banking', 'kind' => 'system', 'name' => 'Internet Banking'],
            ['key' => 'db', 'kind' => 'container', 'name' => 'Database', 'parent_key' => 'banking', 'technology' => 'PostgreSQL', 'form' => 'database'],
            ['key' => 'mq', 'kind' => 'container', 'name' => 'Orders queue', 'parent_key' => 'banking', 'technology' => 'RabbitMQ', 'form' => 'queue'],
            ['key' => 'legacyDb', 'kind' => 'system', 'name' => 'Legacy DB', 'external' => true, 'form' => 'database'],
        ];

        $container = $generator->toContainer($elements, [], 'banking');
        $this->assertStringContainsString('ContainerDb(db, "Database", "PostgreSQL")', $container);
        $this->assertStringContainsString('ContainerQueue(mq, "Orders queue", "RabbitMQ")', $container);
        $this->assertStringContainsString('SystemDb_Ext(legacyDb, "Legacy DB")', $container);
        $this->assertStringContainsString('UpdateLayoutConfig($c4ShapeInRow="4", $c4BoundaryInRow="2")', $container);

        $dsl = (new StructurizrExporter())->toDsl('Demo', $elements, []);
        $this->assertStringContainsString('tags "Database"', $dsl);
        $this->assertStringContainsString('tags "Queue"', $dsl);
    }

    public function test_layout_config_and_directional_rels(): void
    {
        $generator = new C4MermaidGenerator();
        $elements = [
            ['key' => 'customer', 'kind' => 'person', 'name' => 'Customer'],
            ['key' => 'app', 'kind' => 'system', 'name' => 'App'],
        ];
        $relationships = [
            ['from_key' => 'customer', 'to_key' => 'app', 'label' => 'Uses', 'direction' => 'right'],
        ];

        $mermaid = $generator->toContext($elements, $relationships, [
            'shapes_per_row' => 2,
            'boundaries_per_row' => 1,
        ]);

        $this->assertStringContainsString('Rel_R(customer, app, "Uses")', $mermaid);
        $this->assertStringContainsString('UpdateLayoutConfig($c4ShapeInRow="2", $c4BoundaryInRow="1")', $mermaid);
    }

    public function test_structurizr_dsl_and_json_export(): void
    {
        $exporter = new StructurizrExporter();
        $elements = [
            ['key' => 'user', 'kind' => 'person', 'name' => 'User'],
            ['key' => 'app', 'kind' => 'system', 'name' => 'App'],
            ['key' => 'web', 'kind' => 'container', 'name' => 'Web', 'parent_key' => 'app'],
        ];
        $relationships = [
            ['from_key' => 'user', 'to_key' => 'app', 'label' => 'Uses'],
        ];

        $dsl = $exporter->toDsl('Demo', $elements, $relationships);
        $this->assertStringContainsString('workspace "Demo"', $dsl);
        $this->assertStringContainsString('user = person "User"', $dsl);
        $this->assertStringContainsString('app = softwareSystem "App"', $dsl);
        $this->assertStringContainsString('web = container "Web"', $dsl);
        $this->assertStringContainsString('user -> app "Uses"', $dsl);
        $this->assertStringContainsString('systemContext app', $dsl);
        $this->assertStringContainsString('container app', $dsl);
        $this->assertStringContainsString('component web', $dsl);

        $json = $exporter->toJson('Demo', $elements, $relationships);
        $this->assertSame('Demo', $json['name']);
        $this->assertNotEmpty($json['model']['people']);
        $this->assertNotEmpty($json['model']['softwareSystems']);
        $this->assertNotEmpty($json['views']['systemContextViews']);
    }

    public function test_structurizr_dsl_exports_groups(): void
    {
        $exporter = new StructurizrExporter();
        $elements = [
            ['key' => 'internal', 'kind' => 'group', 'name' => 'Internal'],
            ['key' => 'app', 'kind' => 'system', 'name' => 'App', 'parent_key' => 'internal'],
        ];

        $dsl = $exporter->toDsl('Demo', $elements, []);
        $this->assertStringContainsString('group "Internal" {', $dsl);
        $this->assertStringContainsString('app = softwareSystem "App"', $dsl);
    }
}
