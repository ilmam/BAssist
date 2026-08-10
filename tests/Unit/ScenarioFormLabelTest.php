<?php

namespace Tests\Unit;

use Tests\TestCase;

class ScenarioFormLabelTest extends TestCase
{
    public function test_scenario_content_label_is_unified(): void
    {
        $this->assertSame('Scenario Content', __('ui.scenario_document'));
        $this->assertSame('Document', __('ui.body'));
        $this->assertSame('Feature document', __('ui.feature_document'));
    }

    public function test_scenario_form_blades_hide_body_field_label(): void
    {
        $page = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/scenarios/form.blade.php');
        $modal = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/scenarios/modals/form.blade.php');

        $this->assertIsString($page);
        $this->assertIsString($modal);

        $this->assertStringContainsString("__('ui.scenario_document')", $page);
        $this->assertStringContainsString("__('ui.scenario_document')", $modal);
        $this->assertStringContainsString("'label' => ''", $page);
        $this->assertStringContainsString("'label' => ''", $modal);
    }

    public function test_scenario_view_uses_single_scenario_content_heading(): void
    {
        $partial = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/scenarios/partials/view-content.blade.php');

        $this->assertIsString($partial);
        $this->assertStringContainsString("__('ui.scenario_document')", $partial);
        $this->assertSame(1, substr_count($partial, "__('ui.scenario_document')"));
    }
}
