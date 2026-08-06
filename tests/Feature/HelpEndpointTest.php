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

    public function test_readiness_help_route_returns_rendered_guide(): void
    {
        $response = $this->withoutMiddleware()->get(route('readiness.help'));

        $response->assertOk();
        $response->assertSee('data-help-title', false);
        $response->assertSee('Assess enterprise readiness', false);
        $response->assertSee('Gap analysis', false);
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

    public function test_project_dashboard_wires_readiness_help_not_title_help(): void
    {
        $blade = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/projects/dashboard.blade.php');

        $this->assertIsString($blade);
        $this->assertStringContainsString('<x-help-trigger topic="readiness" />', $blade);
        $this->assertStringNotContainsString('<x-help-trigger topic="projects" />', $blade);
    }

    public function test_strategic_baseline_help_route_returns_rendered_guide(): void
    {
        $response = $this->withoutMiddleware()->get(route('strategic_baselines.help'));

        $response->assertOk();
        $response->assertSee('data-help-title', false);
        $response->assertSee('Strategic Baseline', false);
        $response->assertSee('Current state', false);
        $response->assertSee('Change strategy', false);
        $response->assertSee('help-guide', false);
    }

    public function test_strategic_baseline_form_and_details_wire_help_trigger(): void
    {
        $form = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/strategic_baselines/form.blade.php');
        $details = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/strategic_baselines/details.blade.php');

        $this->assertIsString($form);
        $this->assertIsString($details);
        $this->assertStringContainsString('<x-help-trigger model="StrategicBaseline" />', $form);
        $this->assertStringContainsString('<x-help-trigger model="StrategicBaseline" />', $details);
    }
}
