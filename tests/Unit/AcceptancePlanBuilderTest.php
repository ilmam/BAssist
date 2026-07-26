<?php

namespace Tests\Unit;

use App\Models\Feature;
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
            'body' => "@happy-path\nScenario: Valid schedule",
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
        $this->assertSame('Must validate dates', $rows[0]['rule']);

        $this->assertSame('FE-1-002', $rows[1]['test_id']);
        $this->assertSame(AcceptancePlanBuilder::TYPE_EDGE_CASE, $rows[1]['type']);
        $this->assertSame(AcceptancePlanBuilder::STATUS_DEFAULT, $rows[1]['status']);
        $this->assertSame(10, $rows[1]['feature_id']);
        $this->assertSame(101, $rows[1]['scenario_id']);
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
}
