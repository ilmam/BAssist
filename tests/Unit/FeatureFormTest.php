<?php

namespace Tests\Unit;

use Tests\TestCase;

class FeatureFormTest extends TestCase
{
    /**
     * Feature's custom form blades hand-pick which $formFields render in the
     * Traceability section (unlike FR, which uses the generic <x-form> loop).
     * change_request_id previously existed on FeatureData but was silently
     * dropped from both blades — guard against that regression.
     */
    public function test_form_blades_render_change_request_field(): void
    {
        $page = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/features/form.blade.php');
        $modal = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/features/modals/form.blade.php');

        $this->assertIsString($page);
        $this->assertIsString($modal);
        $this->assertStringContainsString("'change_request_id'", $page);
        $this->assertStringContainsString("'change_request_id'", $modal);
    }
}
