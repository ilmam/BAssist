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
}
