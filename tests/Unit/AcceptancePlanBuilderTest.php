<?php

namespace Tests\Unit;

use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\Scenario;
use App\Models\Status;
use App\Services\AcceptancePlanBuilder;
use App\Support\ProjectContext;
use App\Support\WorkspaceContext;
use PHPUnit\Framework\TestCase;

class AcceptancePlanBuilderTest extends TestCase
{
    protected function builder(): AcceptancePlanBuilder
    {
        return new AcceptancePlanBuilder(
            $this->createStub(WorkspaceContext::class),
            $this->createStub(ProjectContext::class),
        );
    }

    /**
     * KTSelect fires change on init; onchange→form.submit() caused an infinite reload loop.
     */
    public function test_acceptance_plan_filters_do_not_auto_submit_on_change(): void
    {
        $blade = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/acceptance-plan/index.blade.php');

        $this->assertIsString($blade);
        $this->assertStringNotContainsString('this.form.submit()', $blade);
        $this->assertStringContainsString("__('ui.apply_filters')", $blade);
    }

    public function test_type_edge_case_from_scenario_tag(): void
    {
        $builder = $this->builder();

        $this->assertSame(
            AcceptancePlanBuilder::TYPE_EDGE_CASE,
            $builder->resolveType("@happy-path\nFeature: X", "@edge-case\nScenario: Bad input")
        );
    }

    public function test_type_edge_case_from_feature_body(): void
    {
        $builder = $this->builder();

        $this->assertSame(
            AcceptancePlanBuilder::TYPE_EDGE_CASE,
            $builder->resolveType("@edge-case\nFeature: X", "Scenario: Something")
        );
    }

    public function test_type_defaults_to_happy_path(): void
    {
        $builder = $this->builder();

        $this->assertSame(
            AcceptancePlanBuilder::TYPE_HAPPY_PATH,
            $builder->resolveType("@happy-path\nFeature: X", "Scenario: Ok")
        );
        $this->assertSame(
            AcceptancePlanBuilder::TYPE_HAPPY_PATH,
            $builder->resolveType(null, null)
        );
    }

    public function test_test_id_prefers_feature_code_then_title_initials(): void
    {
        $builder = $this->builder();

        $this->assertSame('FE-3', $builder->testIdPrefix('FE-3', 'Team Schedule Management'));
        $this->assertSame('TSM', $builder->testIdPrefix(null, 'Team Schedule Management'));
        $this->assertSame('FE-3-001', $builder->formatTestId('FE-3', 1));
        $this->assertSame('TSM-002', $builder->formatTestId('TSM', 2));
    }

    public function test_rows_sequence_type_and_status_per_feature(): void
    {
        $builder = $this->builder();

        $agreed = new Status(['name' => 'Agreed']);
        $feature = new Feature([
            'title' => 'Team Schedule Management',
            'body' => "@happy-path\nFeature: Team Schedule Management\nRule: Must validate dates",
        ]);
        $feature->id = 10;
        $feature->number = 1;
        $feature->stakeholder_need_id = 5;
        $feature->setRelation('status', null);
        $feature->setRelation('stakeholderNeed', null);

        $happy = new Scenario([
            'title' => 'Valid schedule',
            'body' => "@happy-path\nScenario: Valid schedule\nWhen dates are valid\nThen the schedule is saved",
        ]);
        $happy->id = 100;
        $happy->setRelation('status', $agreed);

        $edge = new Scenario([
            'title' => 'Invalid dates',
            'body' => "@edge-case\nScenario: Invalid dates",
        ]);
        $edge->id = 101;
        $edge->setRelation('status', null);

        $feature->setRelation('scenarios', collect([$happy, $edge]));

        $rows = $builder->rowsForFeatures([$feature]);

        $this->assertCount(2, $rows);
        $this->assertSame('FE-1-001', $rows[0]['test_id']);
        $this->assertSame(AcceptancePlanBuilder::TYPE_HAPPY_PATH, $rows[0]['type']);
        $this->assertSame('Agreed', $rows[0]['status']);
        // Explicit Rule: wins over scenario When/Then.
        $this->assertSame('Must validate dates', $rows[0]['rule']);

        $this->assertSame('FE-1-002', $rows[1]['test_id']);
        $this->assertSame(AcceptancePlanBuilder::TYPE_EDGE_CASE, $rows[1]['type']);
        $this->assertSame(AcceptancePlanBuilder::STATUS_DEFAULT, $rows[1]['status']);
        $this->assertSame(10, $rows[1]['feature_id']);
        $this->assertSame(101, $rows[1]['scenario_id']);
    }

    public function test_bdd_rule_uses_feature_story_not_scenario_steps(): void
    {
        $builder = $this->builder();

        $this->assertSame(
            'As a Parts Field Agent I want inquiry status to advance So that procurement is safe',
            $builder->resolveBddRule(
                "Feature: Inquiry\nAs a Parts Field Agent\nI want inquiry status to advance\nSo that procurement is safe"
            )
        );

        // Scenario When/Then must not become the parent statement.
        $this->assertSame(
            'As a Agent I want to submit',
            $builder->resolveBddRule("Feature: Inquiry\nAs a Agent\nI want to submit")
        );
    }

    public function test_status_falls_back_to_feature_then_draft(): void
    {
        $builder = $this->builder();

        $featureStatus = new Status(['name' => 'Deprecated']);
        $feature = new Feature(['title' => 'X', 'body' => 'Feature: X']);
        $feature->id = 1;
        $feature->number = 2;
        $feature->setRelation('status', $featureStatus);
        $feature->setRelation('stakeholderNeed', null);

        $scenario = new Scenario(['title' => 'S', 'body' => 'Scenario: S']);
        $scenario->id = 1;
        $scenario->setRelation('status', null);
        $feature->setRelation('scenarios', collect([$scenario]));

        $rows = $builder->rowsForFeatures([$feature]);
        $this->assertSame('Deprecated', $rows[0]['status']);

        $feature->setRelation('status', null);
        $rows = $builder->rowsForFeatures([$feature]);
        $this->assertSame('Draft', $rows[0]['status']);
    }

    public function test_acceptance_criteria_lines_strip_bullets(): void
    {
        $builder = $this->builder();

        $this->assertSame([
            'Duration equals Close minus Open in whole minutes',
            'Open without Close leaves duration blank',
            'Edge: Close before Open is rejected',
        ], $builder->acceptanceCriteriaLines(<<<'TXT'
- Duration equals Close minus Open in whole minutes
* Open without Close leaves duration blank
1. Edge: Close before Open is rejected
TXT));
    }

    public function test_rows_from_functional_requirement_acceptance_criteria(): void
    {
        $builder = $this->builder();

        $requirement = new FunctionalRequirement([
            'title' => 'Ticket duration calculation',
            'statement' => 'The system shall calculate ticket duration from Open and Close timestamps.',
            'acceptance_criteria' => <<<'TXT'
- Duration equals Close minus Open in whole minutes
- Edge: Close before Open is rejected
TXT,
        ]);
        $requirement->id = 7;
        $requirement->number = 1;
        $requirement->stakeholder_need_id = 3;
        $requirement->setRelation('status', new Status(['name' => 'Draft']));
        $requirement->setRelation('stakeholderNeed', null);

        $rows = $builder->rowsForFunctionalRequirements([$requirement]);

        $this->assertCount(2, $rows);
        $this->assertSame(AcceptancePlanBuilder::SOURCE_FR, $rows[0]['source']);
        $this->assertSame('FR-1-001', $rows[0]['test_id']);
        $this->assertSame('Duration equals Close minus Open in whole minutes', $rows[0]['scenario_title']);
        $this->assertSame(AcceptancePlanBuilder::TYPE_HAPPY_PATH, $rows[0]['type']);
        $this->assertSame(
            'The system shall calculate ticket duration from Open and Close timestamps.',
            $rows[0]['rule']
        );

        $this->assertSame('FR-1-002', $rows[1]['test_id']);
        $this->assertSame(AcceptancePlanBuilder::TYPE_EDGE_CASE, $rows[1]['type']);
        $this->assertSame(7, $rows[1]['functional_requirement_id']);
        $this->assertNull($rows[1]['feature_id']);
    }

    public function test_functional_requirement_without_acceptance_criteria_yields_no_rows(): void
    {
        $requirement = new FunctionalRequirement([
            'title' => 'Empty AC',
            'statement' => 'The system shall do something.',
            'acceptance_criteria' => null,
        ]);
        $requirement->id = 1;
        $requirement->number = 2;
        $requirement->setRelation('status', null);
        $requirement->setRelation('stakeholderNeed', null);

        $this->assertSame([], $this->builder()->rowsForFunctionalRequirements([$requirement]));
    }
}
