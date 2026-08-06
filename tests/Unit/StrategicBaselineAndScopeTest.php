<?php

namespace Tests\Unit;

use App\Data\ScopeItemData;
use App\Data\StrategicBaselineData;
use App\Models\StrategicBaseline;
use App\Support\EntityFormBuilder;
use App\Support\ScopeItemDirection;
use App\Support\StrategicBaselineStatus;
use Tests\TestCase;

class StrategicBaselineAndScopeTest extends TestCase
{
    public function test_strategic_baseline_status_defaults(): void
    {
        $this->assertSame('draft', StrategicBaselineStatus::default());
        $this->assertSame(
            ['draft', 'in_review', 'approved'],
            StrategicBaselineStatus::values()
        );
    }

    public function test_scope_item_direction_defaults(): void
    {
        $this->assertSame('in', ScopeItemDirection::default());
        $this->assertSame(['in', 'out'], ScopeItemDirection::values());
    }

    public function test_strategic_baseline_form_resolves_status_select_options(): void
    {
        $fields = (new EntityFormBuilder)->fields(StrategicBaselineData::class);

        $this->assertSame('select', $fields['status']['type'] ?? null);
        $this->assertArrayHasKey('list', $fields['status']);
        $this->assertEqualsCanonicalizing(
            StrategicBaselineStatus::values(),
            array_keys($fields['status']['list'])
        );
    }

    public function test_scope_item_form_resolves_direction_select_options(): void
    {
        $fields = (new EntityFormBuilder)->fields(ScopeItemData::class);

        $this->assertSame('select', $fields['direction']['type'] ?? null);
        $this->assertArrayHasKey('list', $fields['direction']);
        $this->assertEqualsCanonicalizing(
            ScopeItemDirection::values(),
            array_keys($fields['direction']['list'])
        );
        $this->assertArrayNotHasKey('business_need_id', $fields);
        $this->assertArrayNotHasKey('status_id', $fields);
    }

    public function test_strategy_hub_mirrors_diagrams_pattern(): void
    {
        $blade = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/strategy/index.blade.php');

        $this->assertIsString($blade);
        $this->assertStringContainsString("route('strategy.index')", $blade);
        $this->assertStringContainsString("__('ui.strategy')", $blade);
        $this->assertStringContainsString('$section[\'items\']', $blade);
        $this->assertStringContainsString('<x-help-trigger :model="$section[\'model\']" />', $blade);
        $this->assertStringContainsString('<x-help-trigger topic="strategy" />', $blade);
    }

    public function test_strategic_baseline_has_strategy_content(): void
    {
        $empty = new StrategicBaseline([
            'current_state' => null,
            'future_state' => '   ',
            'change_strategy' => '',
        ]);
        $this->assertFalse($empty->hasStrategyContent());

        $partial = new StrategicBaseline([
            'current_state' => 'Dealers call Parts Field; regional spreadsheets.',
            'future_state' => null,
            'change_strategy' => null,
        ]);
        $this->assertTrue($partial->hasStrategyContent());
    }

    public function test_readiness_lang_covers_strategy_and_scope_gaps(): void
    {
        $this->assertNotSame('ui.readiness_no_strategic_baseline', __('ui.readiness_no_strategic_baseline'));
        $this->assertNotSame('ui.readiness_strategic_baseline_draft', __('ui.readiness_strategic_baseline_draft'));
        $this->assertNotSame('ui.readiness_no_scope_items', __('ui.readiness_no_scope_items'));
    }

    public function test_export_pack_includes_strategy_sections(): void
    {
        $blade = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/projects/export.blade.php');

        $this->assertIsString($blade);
        $this->assertStringContainsString("__('ui.strategic_baseline')", $blade);
        $this->assertStringContainsString("__('ui.current_state')", $blade);
        $this->assertStringContainsString("__('ui.future_state')", $blade);
        $this->assertStringContainsString("__('ui.change_strategy')", $blade);
        $this->assertStringContainsString("__('ui.scope_items')", $blade);
        $this->assertStringContainsString('scope_item_direction_in', $blade);
        $this->assertStringContainsString('scope_item_direction_out', $blade);
    }
}
