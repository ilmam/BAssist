<?php

namespace Tests\Feature;

use Tests\TestCase;

class HelpGuideBookletTest extends TestCase
{
    public function test_toc_route_returns_contents_with_step_links(): void
    {
        $response = $this->withoutMiddleware()->get(route('help.guide'));

        $response->assertOk();
        $response->assertSee('data-help-title', false);
        $response->assertSee(__('ui.ba_guide'), false);
        $response->assertSee(__('ui.ba_guide_intro'), false);
        $response->assertSee('help-guide-toc', false);
        $response->assertSee(route('help.guide.show', 'business_needs'), false);
        $response->assertSee('data-help-url="'.route('help.guide.show', 'business_objectives').'"', false);
        $response->assertSee('Business Objectives', false);
        $response->assertSee('Functional Requirements', false);
        $response->assertDontSee('Diagrams', false);
        $response->assertDontSee(route('help.guide.show', 'diagrams'), false);
        $response->assertDontSee('Strategy', false);
        $response->assertDontSee(route('help.guide.show', 'strategy'), false);
    }

    public function test_step_route_returns_guide_with_prev_next_nav(): void
    {
        $response = $this->withoutMiddleware()->get(route('help.guide.show', 'business_objectives'));

        $response->assertOk();
        $response->assertSee('help-guide-nav', false);
        $response->assertSee('data-help-url="'.route('help.guide.show', 'business_needs').'"', false);
        $response->assertSee('data-help-url="'.route('help.guide.show', 'risks').'"', false);
        $response->assertSee('data-help-url="'.route('help.guide').'"', false);
        $response->assertSee('data-help-nav="prev"', false);
        $response->assertSee('data-help-nav="next"', false);
        $response->assertSee('data-help-nav="toc"', false);
        $response->assertSee(__('ui.ba_guide_previous'), false);
        $response->assertSee(__('ui.ba_guide_next'), false);
        $response->assertSee(__('ui.ba_guide_contents'), false);
        $response->assertDontSee('Alt ← / →', false);
    }

    public function test_diagrams_markdown_exists_but_is_omitted_from_booklet(): void
    {
        $this->assertFileExists(dirname(__DIR__, 2).'/resources/help/diagrams.md');

        $this->withoutMiddleware()
            ->get(route('help.guide.show', 'diagrams'))
            ->assertNotFound();
    }

    public function test_strategy_markdown_exists_but_is_omitted_from_booklet(): void
    {
        $this->assertFileExists(dirname(__DIR__, 2).'/resources/help/strategy.md');

        $this->withoutMiddleware()
            ->get(route('help.guide.show', 'strategy'))
            ->assertNotFound();
    }

    public function test_unknown_guide_key_returns_not_found(): void
    {
        $response = $this->withoutMiddleware()->get(route('help.guide.show', 'not_a_real_topic'));

        $response->assertNotFound();
    }

    public function test_functional_requirements_guide_is_in_booklet(): void
    {
        $response = $this->withoutMiddleware()->get(route('help.guide.show', 'functional_requirements'));

        $response->assertOk();
        $response->assertSee('Functional Requirements', false);
    }

    public function test_non_functional_requirements_guide_is_in_booklet(): void
    {
        $response = $this->withoutMiddleware()->get(route('help.guide.show', 'non_functional_requirements'));

        $response->assertOk();
        $response->assertSee('Non-Functional Requirements', false);
    }

    public function test_topbar_includes_ba_guide_trigger_with_label(): void
    {
        $blade = file_get_contents(dirname(__DIR__, 2).'/resources/views/themes/metronic9/partials/topbar.blade.php');

        $this->assertIsString($blade);
        $this->assertStringContainsString("route('help.guide')", $blade);
        $this->assertStringContainsString("__('ui.ba_guide')", $blade);
        $this->assertStringContainsString('data-help-url', $blade);
        $this->assertStringContainsString('<span>{{ __(\'ui.ba_guide\') }}</span>', $blade);
    }
}
