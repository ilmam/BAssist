<?php

namespace Tests\Unit;

use App\Data\AssumptionData;
use App\Support\AssumptionStatus;
use App\Support\BusinessRuleStatus;
use App\Support\ConstraintStatus;
use App\Support\EntityFormBuilder;
use App\Support\EntityPriority;
use Tests\TestCase;

class Phase1xGuardrailsTest extends TestCase
{
    public function test_moscow_priority_codes(): void
    {
        $this->assertSame(['must', 'should', 'could', 'wont'], EntityPriority::values());
        $this->assertSame('should', EntityPriority::default());
        $this->assertSame('must', EntityPriority::HIGH);
        $this->assertSame('should', EntityPriority::MEDIUM);
        $this->assertSame('could', EntityPriority::LOW);
    }

    public function test_guardrail_status_defaults(): void
    {
        $this->assertSame('open', AssumptionStatus::default());
        $this->assertSame('active', ConstraintStatus::default());
        $this->assertSame('draft', BusinessRuleStatus::default());
    }

    public function test_assumption_form_resolves_status_select_options(): void
    {
        $fields = (new EntityFormBuilder)->fields(AssumptionData::class);

        $this->assertSame('select', $fields['status']['type'] ?? null);
        $this->assertArrayHasKey('list', $fields['status']);
        $this->assertEqualsCanonicalizing(
            AssumptionStatus::values(),
            array_keys($fields['status']['list'])
        );
    }

    public function test_guardrails_hub_mirrors_diagrams_pattern(): void
    {
        $blade = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/guardrails/index.blade.php');

        $this->assertIsString($blade);
        $this->assertStringContainsString("route('guardrails.index')", $blade);
        $this->assertStringContainsString("__('ui.guardrails')", $blade);
        $this->assertStringContainsString('$section[\'items\']', $blade);
    }

    public function test_project_dashboard_includes_readiness_card(): void
    {
        $blade = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/projects/dashboard.blade.php');

        $this->assertIsString($blade);
        $this->assertStringContainsString("__('ui.project_readiness')", $blade);
        $this->assertStringContainsString('$readiness[\'items\']', $blade);
    }

    public function test_readiness_covers_unsatisfied_design_steps(): void
    {
        $this->assertNotSame(
            'ui.readiness_unsatisfied_design_steps',
            __('ui.readiness_unsatisfied_design_steps')
        );
        $this->assertStringContainsString(
            'Design steps',
            __('ui.readiness_unsatisfied_design_steps')
        );

        $service = file_get_contents(dirname(__DIR__, 2).'/app/Services/ProjectReadinessService.php');
        $this->assertIsString($service);
        $this->assertStringContainsString("key: 'unsatisfied_design_steps'", $service);
        $this->assertStringContainsString('countUnsatisfiedDesignSteps', $service);
    }
}
