<?php

namespace Tests\Feature;

use Tests\TestCase;

class HelpEndpointTest extends TestCase
{
    public function test_entity_help_route_returns_rendered_guide(): void
    {
        $response = $this->withoutMiddleware()->get(route('business_needs.help'));

        $response->assertOk();
        $response->assertSee('data-help-title', false);
        $response->assertSee('Business Needs', false);
        $response->assertSee('help-guide', false);
    }

    public function test_hub_help_route_returns_rendered_guide(): void
    {
        $response = $this->withoutMiddleware()->get(route('strategy.help'));

        $response->assertOk();
        $response->assertSee('Strategy', false);
    }

    public function test_help_route_without_markdown_file_returns_not_found(): void
    {
        $response = $this->withoutMiddleware()->get(route('stakeholders.help'));

        $response->assertNotFound();
    }

    public function test_entity_list_partial_includes_help_trigger(): void
    {
        $blade = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/partials/entity-list.blade.php');

        $this->assertIsString($blade);
        $this->assertStringContainsString('<x-slot:titleAside>', $blade);
        $this->assertStringContainsString('<x-help-trigger :model="$model" />', $blade);
    }

    public function test_hub_section_toolbars_include_per_entity_help_trigger(): void
    {
        foreach (['guardrails', 'strategy', 'diagrams'] as $hub) {
            $blade = file_get_contents(dirname(__DIR__, 2)."/resources/views/pages/{$hub}/index.blade.php");

            $this->assertIsString($blade);
            $this->assertStringContainsString('<x-slot:titleAside>', $blade);
            $this->assertStringContainsString(
                '<x-help-trigger :model="$section[\'model\']" />',
                $blade,
                "Expected per-section help trigger on {$hub} hub."
            );
        }
    }
}
