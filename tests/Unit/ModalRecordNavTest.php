<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModalRecordNavTest extends TestCase
{
    #[Test]
    public function record_nav_partial_renders_controls_when_enabled(): void
    {
        config(['ui.modal_record_nav' => true]);

        $html = view('pages.partials.modal-record-nav')->render();

        $this->assertStringContainsString('data-modal-record-nav-root', $html);
        $this->assertStringContainsString('data-modal-record-nav="prev"', $html);
        $this->assertStringContainsString('data-modal-record-nav="next"', $html);
        $this->assertStringContainsString('data-modal-record-nav-label', $html);
        $this->assertStringContainsString('ki-outline ki-black-left', $html);
        $this->assertStringContainsString('ki-outline ki-black-right', $html);
        $this->assertStringContainsString('aria-label="'.__('ui.previous_record').'"', $html);
        $this->assertStringContainsString('aria-label="'.__('ui.next_record').'"', $html);
        $this->assertStringNotContainsString('>'.__('ui.previous_record').'</button>', $html);
        $this->assertStringNotContainsString('>'.__('ui.next_record').'</button>', $html);
    }

    #[Test]
    public function record_nav_partial_is_empty_when_disabled(): void
    {
        config(['ui.modal_record_nav' => false]);

        $html = trim(view('pages.partials.modal-record-nav')->render());

        $this->assertSame('', $html);
    }

    #[Test]
    public function default_view_modal_includes_record_nav_mount(): void
    {
        $contents = file_get_contents(resource_path('views/pages/modals/view.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("@include('pages.partials.modal-record-nav')", $contents);
    }
}
