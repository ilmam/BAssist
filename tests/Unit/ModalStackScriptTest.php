<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModalStackScriptTest extends TestCase
{
    #[Test]
    public function modal_stack_partial_defines_nested_helpers(): void
    {
        $html = view('pages.partials.modal-stack-script')->render();

        $this->assertStringContainsString('let modalStack = []', $html);
        $this->assertStringContainsString('function pushModalStackIfNeeded', $html);
        $this->assertStringContainsString('function closeModalWithStack', $html);
        $this->assertStringContainsString('function restoreParentModalFromStack', $html);
        $this->assertStringContainsString('function handleModalStackPopState', $html);
        $this->assertStringContainsString('fromStack', $html);
        $this->assertStringContainsString('preserveRecordNav', $html);
        $this->assertStringContainsString('contentClone', $html);
        $this->assertStringContainsString('bassistOpenModalHtml', $html);
    }

    #[Test]
    public function theme_templates_include_modal_stack_script(): void
    {
        foreach (['metronic9', 'metronic8'] as $theme) {
            $contents = file_get_contents(resource_path("views/themes/{$theme}/template.blade.php"));

            $this->assertNotFalse($contents, "Missing theme template: {$theme}");
            $this->assertStringContainsString(
                "@include('pages.partials.modal-stack-script')",
                $contents,
                "Theme {$theme} should include modal stack script"
            );
            $this->assertStringContainsString('pushModalStackIfNeeded', $contents);
            $this->assertStringContainsString('closeModalWithStack', $contents);
            $this->assertStringContainsString('handleModalStackPopState', $contents);
            $this->assertStringContainsString('window.bassistOpenModalHtml', $contents);
        }
    }

    #[Test]
    public function escape_guard_defers_to_open_native_dialog(): void
    {
        $contents = file_get_contents(resource_path('views/pages/partials/modal-close-guard-script.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("document.querySelector('dialog[open]')", $contents);
    }
}
