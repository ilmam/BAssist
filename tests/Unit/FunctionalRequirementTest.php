<?php

namespace Tests\Unit;

use App\Data\FunctionalRequirementData;
use App\Models\FunctionalRequirement;
use App\Support\CrudEntityRegistry;
use App\Support\EntityFormBuilder;
use Tests\TestCase;

class FunctionalRequirementTest extends TestCase
{
    public function test_entity_is_registered_for_crud(): void
    {
        $this->assertContains('FunctionalRequirement', array_keys(CrudEntityRegistry::all()));
    }

    public function test_entity_number_prefix_is_fr(): void
    {
        $method = new \ReflectionMethod(FunctionalRequirement::class, 'entityNumberPrefix');

        $this->assertSame('FR', $method->invoke(null));
    }

    public function test_form_includes_core_fields(): void
    {
        $fields = (new EntityFormBuilder)->fields(FunctionalRequirementData::class);

        $this->assertSame('textarea', $fields['statement']['type'] ?? null);
        $this->assertSame('select', $fields['stakeholder_need_id']['type'] ?? null);
        $this->assertSame('select', $fields['swimlane_flow_step_id']['type'] ?? null);
        $this->assertSame('select', $fields['status_id']['type'] ?? null);
        $this->assertSame('select', $fields['priority_id']['type'] ?? null);
    }

    public function test_solution_requirements_hub_lists_both_dialects(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/SolutionRequirementsController.php');

        $this->assertIsString($controller);
        $this->assertStringContainsString('FunctionalRequirement', $controller);
        $this->assertStringContainsString("'Feature'", $controller);
    }

    public function test_validation_requires_statement(): void
    {
        $rules = FunctionalRequirementData::rules();

        $this->assertContains('required', $rules['statement']);
        $this->assertContains('required', $rules['title']);
        $this->assertContains('required', $rules['project_id']);
    }

    /**
     * Parent lineage is exclusive: SN or CR (see SolutionPackagingParent).
     */
    public function test_parent_lineage_is_exclusive_xor(): void
    {
        $rules = FunctionalRequirementData::rules();

        $this->assertContains('nullable', $rules['stakeholder_need_id']);
        $this->assertContains('required_without:change_request_id', $rules['stakeholder_need_id']);
        $this->assertContains('prohibits:change_request_id', $rules['stakeholder_need_id']);

        $this->assertContains('nullable', $rules['change_request_id']);
        $this->assertContains('required_without:stakeholder_need_id', $rules['change_request_id']);
        $this->assertContains('prohibits:stakeholder_need_id', $rules['change_request_id']);
        $this->assertNotContains('required', $rules['change_request_id']);
        $this->assertNotContains('required', $rules['stakeholder_need_id']);
    }

    /**
     * The select's option list must include a leading blank entry, or the
     * browser auto-selects the first Change Request and the "optional"
     * validation rule becomes unreachable from the UI (mirrors
     * SwimlaneFlowStepRepository::getSelectOptions()).
     */
    public function test_change_request_select_options_include_blank_entry(): void
    {
        $options = (new \App\Repositories\ChangeRequestRepository)->getSelectOptions();

        $this->assertArrayHasKey('', $options);
        $this->assertSame('', $options['']);
    }
}
