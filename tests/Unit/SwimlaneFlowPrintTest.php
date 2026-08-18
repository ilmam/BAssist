<?php

namespace Tests\Unit;

use Tests\TestCase;

class SwimlaneFlowPrintTest extends TestCase
{
    public function test_print_opens_existing_preview_in_new_window(): void
    {
        $editor = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/swimlane_flows/partials/elements-editor.blade.php');
        $js = file_get_contents(dirname(__DIR__, 2).'/resources/js/swimlane-flow-diagram.js');

        $this->assertIsString($editor);
        $this->assertIsString($js);

        $this->assertStringContainsString('data-print-diagram', $editor);
        $this->assertStringContainsString("__('ui.print_diagram')", $editor);
        $this->assertStringContainsString('data-export-diagram-image', $editor);
        $this->assertStringContainsString("__('ui.export_diagram_image')", $editor);
        $this->assertStringNotContainsString('printUrl', $editor);

        $this->assertStringContainsString("window.open('about:blank', '_blank')", $js);
        $this->assertStringContainsString('data-print-diagram', $js);
        $this->assertStringContainsString('data-export-diagram-image', $js);
        $this->assertStringContainsString('downloadDiagramPng', $js);
        $this->assertStringContainsString('XMLSerializer', $js);
        $this->assertStringContainsString('@media print', $js);
        $this->assertStringNotContainsString('data-export-diagram"', $js);
        $this->assertStringNotContainsString('data-print-pack', $js);
    }

    public function test_print_pack_route_and_page_are_removed(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/SwimlaneFlowController.php');
        $details = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/swimlane_flows/details.blade.php');
        $modal = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/swimlane_flows/modals/view.blade.php');

        $this->assertIsString($routes);
        $this->assertIsString($controller);
        $this->assertIsString($details);
        $this->assertIsString($modal);

        $this->assertFileDoesNotExist(dirname(__DIR__, 2).'/resources/views/pages/swimlane_flows/print.blade.php');
        $this->assertStringNotContainsString('swimlane_flows.print', $routes);
        $this->assertStringNotContainsString('function print($id)', $controller);
        $this->assertStringNotContainsString('printUrl', $controller);
        $this->assertStringNotContainsString('printUrl', $details);
        $this->assertStringNotContainsString('printUrl', $modal);
    }
}
