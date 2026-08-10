<?php

namespace Tests\Unit;

use App\Data\NonFunctionalRequirementData;
use App\Models\NonFunctionalRequirement;
use App\Support\CrudEntityRegistry;
use App\Support\NfrCategory;
use Tests\TestCase;

class NonFunctionalRequirementTest extends TestCase
{
    public function test_entity_is_registered_for_crud(): void
    {
        $this->assertContains('NonFunctionalRequirement', array_keys(CrudEntityRegistry::all()));
    }

    public function test_entity_number_prefix_is_nfr(): void
    {
        $method = new \ReflectionMethod(NonFunctionalRequirement::class, 'entityNumberPrefix');

        $this->assertSame('NFR', $method->invoke(null));
    }

    public function test_form_includes_core_fields(): void
    {
        $fields = \App\Support\DtoMetadata::for(NonFunctionalRequirementData::class)->formFields();

        $this->assertSame('select', $fields['category'][0] ?? null);
        $this->assertSame('NfrCategory', $fields['category'][1] ?? null);
        $this->assertSame('textarea', $fields['description'][0] ?? null);
        $this->assertSame('select', $fields['stakeholder_need_id'][0] ?? null);
        $this->assertSame('select', $fields['change_request_id'][0] ?? null);
        $this->assertArrayNotHasKey('swimlane_flow_step_id', $fields);
        $this->assertArrayNotHasKey('trigger', $fields);
    }

    public function test_solution_requirements_hub_lists_nfr_dialect(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/SolutionRequirementsController.php');

        $this->assertIsString($controller);
        $this->assertStringContainsString('NonFunctionalRequirement', $controller);
        $this->assertStringContainsString('FunctionalRequirement', $controller);
        $this->assertStringContainsString("'Feature'", $controller);
    }

    public function test_validation_requires_description_and_category(): void
    {
        $rules = NonFunctionalRequirementData::rules();

        $this->assertContains('required', $rules['description']);
        $this->assertContains('required', $rules['category']);
        $this->assertContains('required', $rules['title']);
        $this->assertContains('required', $rules['project_id']);
    }

    public function test_category_options_cover_qos_set(): void
    {
        $this->assertContains(NfrCategory::PERFORMANCE, NfrCategory::values());
        $this->assertContains(NfrCategory::SECURITY, NfrCategory::values());
        $this->assertArrayHasKey(NfrCategory::AVAILABILITY, NfrCategory::selectOptions());
    }

    public function test_parent_lineage_is_exclusive_xor(): void
    {
        $rules = NonFunctionalRequirementData::rules();

        $this->assertContains('nullable', $rules['stakeholder_need_id']);
        $this->assertContains('required_without:change_request_id', $rules['stakeholder_need_id']);
        $this->assertContains('prohibits:change_request_id', $rules['stakeholder_need_id']);

        $this->assertContains('nullable', $rules['change_request_id']);
        $this->assertContains('required_without:stakeholder_need_id', $rules['change_request_id']);
        $this->assertContains('prohibits:stakeholder_need_id', $rules['change_request_id']);
    }
}
