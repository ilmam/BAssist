<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\EntityAccess;
use App\Support\ProjectAccessLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsProjectAccess;
use Tests\TestCase;

class GuestPagesTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProjectAccess;

    protected User $guest;

    /** @var array<string, mixed> */
    protected array $a;

    protected function setUp(): void
    {
        parent::setUp();

        $this->a = $this->seedTenant('Alpha');
        $this->seedSiblingProject($this->a['workspace'], 'Sibling');
        $this->seedTenant('Bravo');

        $this->guest = User::factory()->guest()->create();
        $this->grant($this->guest, $this->a['project'], ProjectAccessLevel::View);
        $this->actingAs($this->guest);
    }

    /**
     * @dataProvider pages
     */
    public function test_report_page_shows_only_the_granted_project(string $route): void
    {
        $response = $this->get(route($route))->assertOk();

        $response->assertSee('Alpha project');
        $response->assertDontSee('Sibling project');
        $response->assertDontSee('Bravo project');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return [
            'diagrams' => ['diagrams.index'],
            'guardrails' => ['guardrails.index'],
            'strategy' => ['strategy.index'],
            'solution requirements' => ['solution_requirements.index'],
            'traceability' => ['traceability.index'],
            'acceptance plan' => ['acceptance-plan.index'],
        ];
    }

    public function test_guest_abilities_cover_reading_but_not_writing_at_view_level(): void
    {
        $this->assertTrue(EntityAccess::can($this->guest, 'FunctionalRequirement', EntityAccess::VIEW));
        $this->assertFalse(EntityAccess::can($this->guest, 'FunctionalRequirement', EntityAccess::UPDATE));
        $this->assertFalse(EntityAccess::can($this->guest, 'Workspace', EntityAccess::VIEW));
        $this->assertContains('FunctionalRequirement', EntityAccess::entitiesFor($this->guest));
        $this->assertNotContains('Workspace', EntityAccess::entitiesFor($this->guest));
    }

    public function test_navigation_tree_lists_only_the_granted_project(): void
    {
        $html = $this->get(route('home'))->getContent();

        $this->assertStringContainsString('Alpha project', $html);
        $this->assertStringNotContainsString('Sibling project', $html);
        $this->assertStringNotContainsString('Bravo project', $html);
    }

    public function test_list_filter_options_offer_only_the_granted_project(): void
    {
        $method = new \ReflectionMethod(\App\Helpers\ListUi::class, 'optionsForFilterParam');
        $options = $method->invoke(null, 'project_id', []);
        $labels = collect($options)->pluck('label')->implode('|');

        $this->assertStringContainsString('Alpha project', $labels);
        $this->assertStringNotContainsString('Sibling project', $labels);
        $this->assertStringNotContainsString('Bravo project', $labels);
    }
}
