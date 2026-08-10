<?php

namespace Tests\Unit;

use App\Facades\Form;
use Tests\TestCase;

class UiFormControlsTest extends TestCase
{
    public function test_checkbox_with_empty_list_renders_default_input(): void
    {
        // Mimics Form('checkbox') fields (e.g. ScenarioData::$is_outline): field.blade.php
        // sets $list = $list ?? [], so the checkbox control receives [] instead of null.
        $html = (string) Form::field('checkbox', 'is_outline', false, [], [
            'help' => 'Usually derived from the document.',
        ]);

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('name="is_outline"', $html);
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringContainsString('Usually derived from the document.', $html);
    }

    public function test_select_defaults_to_kt_select_when_configured(): void
    {
        config(['ui.forms.select' => 'kt']);

        [$attrs] = ui_form_select_attrs();

        $this->assertSame('kt-select', $attrs['class']);
        $this->assertSame('true', $attrs['data-kt-select']);
    }

    public function test_select_can_force_native(): void
    {
        config(['ui.forms.select' => 'kt']);

        [$attrs] = ui_form_select_attrs(['kt_select' => false]);

        $this->assertSame('kt-input', $attrs['class']);
        $this->assertArrayNotHasKey('data-kt-select', $attrs);
    }

    public function test_select_can_force_kt_via_type_flag(): void
    {
        config(['ui.forms.select' => 'native']);

        [$attrs] = ui_form_select_attrs([], forceKtSelect: true);

        $this->assertSame('kt-select', $attrs['class']);
        $this->assertSame('true', $attrs['data-kt-select']);
    }

    public function test_button_classes_use_canonical_variants_and_sizes(): void
    {
        config(['ui.buttons.size' => 'md']);

        $this->assertSame('kt-btn kt-btn-primary', ui_btn_classes('primary'));
        $this->assertSame('kt-btn kt-btn-outline kt-btn-sm', ui_btn_classes('outline', 'sm'));
        $this->assertSame('kt-btn kt-btn-ghost kt-btn-icon', ui_btn_classes('light', 'md', true));
        $this->assertSame('kt-btn kt-btn-destructive', ui_btn_classes('danger'));
        $this->assertSame('kt-btn kt-btn-secondary kt-btn-lg', ui_btn_classes('secondary', 'lg'));
    }
}
