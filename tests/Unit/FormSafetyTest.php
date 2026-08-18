<?php

namespace Tests\Unit;

use Tests\TestCase;

class FormSafetyTest extends TestCase
{
    public function test_form_safety_script_handles_save_shortcut_and_unsaved_leave(): void
    {
        $js = file_get_contents(dirname(__DIR__, 2).'/resources/js/form-safety.js');

        $this->assertIsString($js);
        // A hidden control named "id" shadows HTMLFormElement.id, so the guard
        // must read the attribute instead of the property.
        $this->assertStringNotContainsString('form.id ===', $js);
        $this->assertStringContainsString("form?.getAttribute?.('id')", $js);
        $this->assertStringContainsString("id === 'form1'", $js);
        $this->assertStringContainsString("id === 'modalForm'", $js);
        $this->assertStringContainsString('data-modal-form', $js);
        $this->assertStringContainsString("event.code === 'KeyS'", $js);
        $this->assertStringContainsString('aria-keyshortcuts', $js);
        $this->assertStringContainsString('beforeunload', $js);
        $this->assertStringContainsString("event.returnValue = ''", $js);
        $this->assertStringContainsString('data-editor-dirty', $js);
        $this->assertStringContainsString('allowUnload', $js);
        $this->assertStringContainsString('data-unsaved-changes-leave', $js);
        $this->assertStringContainsString("addEventListener('keydown', handleSaveShortcut, true)", $js);
        $this->assertStringContainsString("addEventListener('click', handleLeaveClick, true)", $js);
        $this->assertStringContainsString('event.isTrusted', $js);
        $this->assertStringContainsString("data-form-safety", $js);
    }

    public function test_alt_s_saves_in_place_without_navigating_or_closing(): void
    {
        $js = file_get_contents(dirname(__DIR__, 2).'/resources/js/form-safety.js');

        // Alt+S posts through the same endpoint instead of clicking Save,
        // so nothing navigates and no modal closes.
        $this->assertStringContainsString('saveFormInPlace(form)', $js);
        $this->assertStringContainsString("'X-Requested-With': 'XMLHttpRequest'", $js);

        // A create form adopts the new id so the next Alt+S is an update.
        $this->assertStringContainsString('adoptSavedRecord', $js);
        $this->assertStringContainsString('record.update_url', $js);
        $this->assertStringContainsString("setHiddenValue(form, '_method', 'PUT')", $js);

        // Re-entrancy guard: a slow save cannot be submitted twice.
        $this->assertStringContainsString('savingForms', $js);

        // Dirty state is cleared and the user gets feedback.
        $this->assertStringContainsString("bassist:form-saved", $js);
        $this->assertStringContainsString('captureFormBaseline(form)', $js);
        $this->assertStringContainsString('showSavedNotice(form)', $js);
        $this->assertStringContainsString('data-record-saved', $js);
    }

    public function test_swimlane_editor_clears_dirty_flags_on_save_in_place(): void
    {
        $js = file_get_contents(dirname(__DIR__, 2).'/resources/js/swimlane-flow-diagram.js');

        $this->assertStringContainsString("addEventListener('bassist:form-saved'", $js);
        $this->assertStringContainsString('setSourceDirty(false)', $js);
        $this->assertStringContainsString('setTableDirty(false)', $js);
    }

    public function test_vite_and_theme_templates_include_form_safety(): void
    {
        $vite = file_get_contents(dirname(__DIR__, 2).'/vite.config.js');
        $this->assertIsString($vite);
        $this->assertStringContainsString("'resources/js/form-safety.js'", $vite);

        foreach (['metronic9', 'metronic8'] as $theme) {
            $template = file_get_contents(resource_path("views/themes/{$theme}/template.blade.php"));
            $this->assertNotFalse($template, "Missing theme template: {$theme}");
            $this->assertStringContainsString('resources/js/form-safety.js', $template);
            $this->assertStringContainsString("@vite(['resources/js/form-safety.js'", $template);
            $this->assertStringContainsString("__('ui.unsaved_changes_leave')", $template);
            $this->assertStringContainsString("__('ui.save_shortcut')", $template);
            $this->assertStringContainsString("__('ui.record_saved')", $template);
        }
    }

    public function test_entity_forms_use_expected_ids_and_submit_buttons(): void
    {
        $page = file_get_contents(resource_path('views/pages/swimlane_flows/form.blade.php'));
        $modal = file_get_contents(resource_path('views/pages/swimlane_flows/modals/form.blade.php'));
        $generic = file_get_contents(resource_path('views/themes/metronic9/components/form.blade.php'));

        $this->assertIsString($page);
        $this->assertIsString($modal);
        $this->assertIsString($generic);

        $this->assertStringContainsString("'id' => 'form1'", $page);
        $this->assertStringContainsString('type="submit"', $page);

        $this->assertStringContainsString("'id' => 'modalForm'", $modal);
        $this->assertStringContainsString("'data-modal-form' => 'true'", $modal);
        $this->assertStringContainsString('type="submit"', $modal);

        $this->assertStringContainsString("['id' => \$id", $generic);
        $this->assertStringContainsString("'data-modal-form' => 'true'", $generic);
        $this->assertStringContainsString('type="submit"', $generic);
    }

    public function test_i18n_and_dirty_hooks_are_present(): void
    {
        $ui = file_get_contents(dirname(__DIR__, 2).'/lang/en/ui.php');
        $swimlane = file_get_contents(dirname(__DIR__, 2).'/resources/js/swimlane-flow-diagram.js');
        $guard = file_get_contents(resource_path('views/pages/partials/modal-close-guard-script.blade.php'));

        $this->assertIsString($ui);
        $this->assertIsString($swimlane);
        $this->assertIsString($guard);

        $this->assertStringContainsString("'unsaved_changes_leave'", $ui);
        $this->assertStringContainsString("'save_shortcut'", $ui);

        $this->assertStringContainsString("setAttribute('data-editor-dirty'", $swimlane);
        $this->assertStringContainsString('setTableDirty', $swimlane);
        $this->assertStringContainsString('querySelector(\'[data-editor-dirty]\')', $guard);
        $this->assertStringContainsString('data-quick-create-form', $guard);
    }
}
