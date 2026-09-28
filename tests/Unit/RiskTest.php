<?php

namespace Tests\Unit;

use App\Data\RiskData;
use App\Data\RiskViewData;
use App\Models\Risk;
use App\Support\CrudEntityRegistry;
use App\Support\DtoMetadata;
use App\Support\RiskCategory;
use App\Support\RiskImpact;
use App\Support\RiskLikelihood;
use App\Support\RiskResponse;
use App\Support\RiskScore;
use App\Support\RiskStatus;
use Tests\TestCase;

class RiskTest extends TestCase
{
    public function test_entity_is_registered_for_crud(): void
    {
        $this->assertContains('Risk', array_keys(CrudEntityRegistry::all()));
    }

    public function test_entity_number_prefix_is_rsk(): void
    {
        $method = new \ReflectionMethod(Risk::class, 'entityNumberPrefix');

        $this->assertSame('RSK', $method->invoke(null));
    }

    public function test_form_exposes_core_risk_fields(): void
    {
        $fields = DtoMetadata::for(RiskData::class)->formFields();

        $this->assertSame('select', $fields['category'][0] ?? null);
        $this->assertSame('RiskCategory', $fields['category'][1] ?? null);
        $this->assertSame('select', $fields['likelihood'][0] ?? null);
        $this->assertSame('select', $fields['impact'][0] ?? null);
        $this->assertSame('select', $fields['response'][0] ?? null);
        $this->assertSame('select', $fields['status'][0] ?? null);
        $this->assertArrayNotHasKey('related_to', $fields);
        $this->assertArrayNotHasKey('source', $fields);
    }

    public function test_source_and_related_to_are_hidden_on_details(): void
    {
        $paths = DtoMetadata::for(RiskViewData::class)->valueFieldPaths(withPrefix: false);

        $this->assertNotContains('source', $paths);
        $this->assertNotContains('related_to', $paths);
    }

    public function test_validation_requires_core_fields(): void
    {
        $rules = $this->entityRules(RiskData::class);

        $this->assertContains('required', $rules['title']);
        $this->assertContains('required', $rules['category']);
        $this->assertContains('required', $rules['likelihood']);
        $this->assertContains('required', $rules['impact']);
        $this->assertContains('required', $rules['status']);
        $this->assertArrayNotHasKey('related_to', $rules);
        $this->assertArrayNotHasKey('source', $rules);
    }

    public function test_score_matrix_bands_high_and_critical(): void
    {
        $this->assertSame(4, RiskScore::calculate(RiskLikelihood::MEDIUM, RiskImpact::MEDIUM));
        $this->assertFalse(RiskScore::isCritical(4));

        $this->assertSame(6, RiskScore::calculate(RiskLikelihood::HIGH, RiskImpact::MEDIUM));
        $this->assertFalse(RiskScore::isCritical(6));
        $this->assertSame(RiskScore::BAND_HIGH, RiskScore::band(6));

        $this->assertSame(9, RiskScore::calculate(RiskLikelihood::HIGH, RiskImpact::HIGH));
        $this->assertTrue(RiskScore::isCritical(9));
        $this->assertSame(RiskScore::BAND_CRITICAL, RiskScore::band(9));
    }

    public function test_model_detects_coverage_gaps(): void
    {
        $criticalWithoutTreatment = new Risk([
            'likelihood' => RiskLikelihood::HIGH,
            'impact' => RiskImpact::HIGH,
            'response' => null,
            'treatment' => null,
        ]);
        $this->assertTrue($criticalWithoutTreatment->isCritical());
        $this->assertFalse($criticalWithoutTreatment->hasCoverageGap());

        $accepted = new Risk([
            'likelihood' => RiskLikelihood::LOW,
            'impact' => RiskImpact::LOW,
            'response' => RiskResponse::ACCEPT,
            'treatment' => null,
        ]);
        $this->assertTrue($accepted->hasCoverageGap());

        $covered = new Risk([
            'likelihood' => RiskLikelihood::HIGH,
            'impact' => RiskImpact::MEDIUM,
            'response' => RiskResponse::MITIGATE,
            'treatment' => 'Run a vendor PoC before contract.',
        ]);
        $this->assertFalse($covered->hasCoverageGap());
    }

    public function test_strategy_nav_includes_risk_leaf(): void
    {
        $folders = collect(config('navigation.hierarchy.project_folders'))->keyBy('key');
        $children = array_map(
            fn (array $child) => $child['entity'] ?? $child['route'] ?? '',
            $folders['strategy']['children']
        );

        $this->assertContains('Risk', $children);
    }

    public function test_risk_list_view_highlights_critical_rows(): void
    {
        $blade = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/risks/list.blade.php');

        $this->assertIsString($blade);
        $this->assertStringContainsString("'rowClassField' => 'is_critical'", $blade);
        $this->assertStringContainsString('is-critical-risk-row', $blade);
        $this->assertStringContainsString('risk-list-score--{score_band}', $blade);
    }

    public function test_readiness_lang_covers_critical_risk_gaps(): void
    {
        $this->assertNotSame('ui.readiness_active_critical_risks', __('ui.readiness_active_critical_risks'));
        $this->assertStringContainsString('Critical', __('ui.readiness_active_critical_risks'));
        $this->assertNotSame('ui.readiness_critical_risks_without_response', __('ui.readiness_critical_risks_without_response'));
        $this->assertNotSame('ui.readiness_critical_risks_without_treatment', __('ui.readiness_critical_risks_without_treatment'));
    }
}
