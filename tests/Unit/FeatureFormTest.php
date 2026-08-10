<?php

namespace Tests\Unit;

use App\Data\FeatureData;
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

    public function test_parent_lineage_is_exclusive_xor(): void
    {
        $rules = FeatureData::rules();

        $this->assertContains('nullable', $rules['stakeholder_need_id']);
        $this->assertContains('required_without:change_request_id', $rules['stakeholder_need_id']);
        $this->assertContains('prohibits:change_request_id', $rules['stakeholder_need_id']);

        $this->assertContains('nullable', $rules['change_request_id']);
        $this->assertContains('required_without:stakeholder_need_id', $rules['change_request_id']);
        $this->assertContains('prohibits:stakeholder_need_id', $rules['change_request_id']);
        $this->assertNotContains('required', $rules['stakeholder_need_id']);
    }

    public function test_details_partial_uses_shared_details_view(): void
    {
        $partial = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/features/partials/view-content.blade.php');

        $this->assertIsString($partial);
        $this->assertStringContainsString('x-details-view', $partial);
        $this->assertStringContainsString(':columns="2"', $partial);
        $this->assertStringNotContainsString("__('ui.traceability') · __('ui.stakeholder_need')", $partial);

        [$beforeRaw, $rawDialog] = array_pad(explode('<dialog', $partial, 2), 2, '');
        // Copy / download / print belong in the raw dialog only.
        $this->assertStringNotContainsString('data-clipboard-from', $beforeRaw);
        $this->assertStringNotContainsString("__('ui.download_feature')", $beforeRaw);
        $this->assertStringNotContainsString("__('ui.print_feature')", $beforeRaw);
        $this->assertStringContainsString("__('ui.print_feature')", $rawDialog);
    }

    public function test_scenarios_panel_skips_view_and_goes_to_edit(): void
    {
        $panel = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/features/partials/scenarios-panel.blade.php');

        $this->assertIsString($panel);
        $this->assertStringNotContainsString("model_modal_path('Scenario', 'view'", $panel);
        $this->assertStringContainsString("model_modal_path('Scenario', 'edit'", $panel);
        $this->assertStringContainsString('space-y-6', $panel);
        $this->assertStringContainsString('space-y-5', $panel);
    }
}
