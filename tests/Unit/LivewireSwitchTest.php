<?php

namespace Tests\Unit;

use App\Support\LivewireSwitch;
use Tests\TestCase;

/**
 * Which screens run as Livewire components: master, area, entity and controller rules.
 */
class LivewireSwitchTest extends TestCase
{
    public function test_it_is_on_by_default(): void
    {
        $this->assertTrue(LivewireSwitch::enabled('forms', 'Risk'));
        $this->assertTrue(LivewireSwitch::enabled('dialogs', 'Risk'));
        $this->assertNull(LivewireSwitch::reason('forms', 'Risk'));
    }

    public function test_the_master_switch_turns_everything_off(): void
    {
        config(['ui.livewire.enabled' => false]);

        $this->assertFalse(LivewireSwitch::enabled('forms', 'Risk'));
        $this->assertFalse(LivewireSwitch::enabled('dialogs', 'Risk'));
        $this->assertStringContainsString('UI_LIVEWIRE', LivewireSwitch::reason('forms', 'Risk'));
    }

    public function test_an_area_switch_turns_only_that_area_off(): void
    {
        config(['ui.livewire.dialogs' => false]);

        $this->assertTrue(LivewireSwitch::enabled('forms', 'Risk'));
        $this->assertFalse(LivewireSwitch::enabled('dialogs', 'Risk'));
    }

    public function test_an_entity_can_opt_out_of_everything_or_of_one_area(): void
    {
        config(['crud.models.Risk.livewire' => false]);
        $this->assertFalse(LivewireSwitch::enabled('forms', 'Risk'));
        $this->assertFalse(LivewireSwitch::enabled('dialogs', 'Risk'));

        config(['crud.models.Risk.livewire' => ['forms' => false]]);
        $this->assertFalse(LivewireSwitch::enabled('forms', 'Risk'));
        $this->assertTrue(LivewireSwitch::enabled('dialogs', 'Risk'));
        $this->assertStringContainsString('opted out', LivewireSwitch::reason('forms', 'Risk'));
    }

    public function test_an_entity_with_its_own_controller_is_always_classic(): void
    {
        config(['crud.models.Risk.controller' => \App\Http\Controllers\CrudController::class]);

        $this->assertFalse(LivewireSwitch::enabled('forms', 'Risk'));
        $this->assertSame('entity has its own controller', LivewireSwitch::reason('dialogs', 'Risk'));
    }
}
