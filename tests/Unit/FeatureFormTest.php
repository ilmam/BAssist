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

    public function test_details_view_two_column_utility_exists(): void
    {
        $detailsView = file_get_contents(dirname(__DIR__, 2).'/resources/views/themes/metronic9/components/details-view.blade.php');
        $css = file_get_contents(dirname(__DIR__, 2).'/public/themes/metronic9/assets/css/bassist.css');

        $this->assertIsString($detailsView);
        $this->assertIsString($css);
        $this->assertStringContainsString('md:grid-cols-2', $detailsView);
        $this->assertMatchesRegularExpression('/\.md\\\\:grid-cols-2\s*\{/', $css);
    }

    public function test_form_blades_use_half_width_for_non_gherkin_fields(): void
    {
        $page = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/features/form.blade.php');
        $modal = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/features/modals/form.blade.php');
        $dtoSource = file_get_contents(dirname(__DIR__, 2).'/app/Data/FeatureData.php');

        $this->assertIsString($page);
        $this->assertIsString($modal);
        $this->assertIsString($dtoSource);

        // Stakeholder Need must follow the default half-width span (not force full row).
        $this->assertDoesNotMatchRegularExpression(
            "/stakeholder_need_id[\s\S]{0,400}data-ui-span-md=\"12\"/",
            $page
        );
        $this->assertDoesNotMatchRegularExpression(
            "/stakeholder_need_id[\s\S]{0,400}data-ui-span-md=\"12\"/",
            $modal
        );
        $this->assertMatchesRegularExpression(
            "/stakeholder_need_id[\s\S]{0,400}data-ui-span-md=\"6\"/",
            $page
        );
        $this->assertMatchesRegularExpression(
            "/stakeholder_need_id[\s\S]{0,400}data-ui-span-md=\"6\"/",
            $modal
        );
        $this->assertDoesNotMatchRegularExpression(
            "/StakeholderNeed[^\n]*uiSpan:\s*12/",
            $dtoSource
        );
    }

    public function test_scenarios_panel_skips_view_and_goes_to_edit(): void
    {
        $panel = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/features/partials/scenarios-panel.blade.php');

        $this->assertIsString($panel);
        $this->assertStringNotContainsString("model_modal_path('Scenario', 'view'", $panel);
        $this->assertStringContainsString("model_modal_path('Scenario', 'edit'", $panel);
        $this->assertStringContainsString('space-y-6', $panel);
        $this->assertStringContainsString('space-y-5', $panel);
        $this->assertMatchesRegularExpression('/editScenarioModalUrl[\s\S]*?color="outline"/', $panel);
        $this->assertMatchesRegularExpression("/Scenario', 'delete'[\s\S]*?color=\"outline\"/", $panel);
        $this->assertDoesNotMatchRegularExpression('/editScenarioModalUrl[\s\S]*?color="primary"/', $panel);
        $this->assertDoesNotMatchRegularExpression("/Scenario', 'delete'[\s\S]*?color=\"danger\"/", $panel);
    }

    public function test_view_content_has_no_mid_page_feature_edit(): void
    {
        $partial = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/features/partials/view-content.blade.php');

        $this->assertIsString($partial);
        $this->assertStringNotContainsString('editFeatureModalUrl', $partial);
        $this->assertStringNotContainsString("__('ui.edit')", $partial);
    }

    public function test_list_view_action_offers_view_raw_split_menu(): void
    {
        $list = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/features/list.blade.php');
        $rawModal = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/features/modals/raw.blade.php');

        $this->assertIsString($list);
        $this->assertIsString($rawModal);
        $this->assertStringContainsString("'actionOverrides'", $list);
        $this->assertStringContainsString("__('ui.view_raw')", $list);
        $this->assertStringContainsString("features/modal/{id}/raw", $list);
        $this->assertStringContainsString('gherkin-document', $rawModal);
        $this->assertStringContainsString("__('ui.view_raw_help')", $rawModal);
    }
}
