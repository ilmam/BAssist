<?php

namespace Tests\Unit;

use App\Models\Feature;
use App\Models\Scenario;
use App\Models\StakeholderNeed;
use App\Services\FeatureImportService;
use App\Services\GherkinDocumentParser;
use Tests\TestCase;

class FeatureImportServiceTest extends TestCase
{
    public function test_split_attaches_tags_and_keeps_comments_in_blocks(): void
    {
        $source = <<<'GHERKIN'
# Feature-level note
@epic:orders
Feature: Place an order
  # narrative comment
  As a customer

# checkout scenarios
@smoke @wip
Scenario: Happy path
  Given I am signed in
  # step note
  When I checkout

@edge
Scenario Outline: Declined card
  Given card <status>
  Examples:
    | status |
    | declined |
GHERKIN;

        $parser = new GherkinDocumentParser;
        $parsed = $parser->splitFeatureFile($source);

        $this->assertStringContainsString('# Feature-level note', $parsed['preamble']);
        $this->assertStringContainsString('# narrative comment', $parsed['preamble']);
        $this->assertStringNotContainsString('@smoke', $parsed['preamble']);
        $this->assertCount(2, $parsed['scenarios']);

        $this->assertStringContainsString('@smoke', $parsed['scenarios'][0]['body']);
        $this->assertStringContainsString('# checkout scenarios', $parsed['scenarios'][0]['body']);
        $this->assertStringContainsString('# step note', $parsed['scenarios'][0]['body']);
        $this->assertSame('Happy path', $parsed['scenarios'][0]['title']);

        $this->assertStringContainsString('@edge', $parsed['scenarios'][1]['body']);
        $this->assertTrue($parsed['scenarios'][1]['is_outline']);
    }

    public function test_preview_replace_reports_title_and_scenario_diffs(): void
    {
        $feature = new Feature([
            'title' => 'Old title',
            'body' => "Feature: Old title\n",
            'stakeholder_need_id' => 1,
        ]);
        $feature->id = 10;
        $feature->project_id = 3;
        $need = new StakeholderNeed(['title' => 'Need']);
        $need->forceFill(['number' => 1]);
        $feature->setRelation('stakeholderNeed', $need);
        $feature->setRelation('scenarios', collect([
            new Scenario(['title' => 'Keep me', 'body' => "Scenario: Keep me\n"]),
            new Scenario(['title' => 'Remove me', 'body' => "Scenario: Remove me\n"]),
        ]));

        $source = <<<'GHERKIN'
@need:SN-9
Feature: New title

@smoke
Scenario: Keep me
  Given x

Scenario: Brand new
  Given y
GHERKIN;

        $preview = (new FeatureImportService)->previewReplace($feature, $source, 'demo.feature');

        $this->assertTrue($preview->titleMismatch);
        $this->assertSame('New title', $preview->incomingTitle);
        $this->assertSame(['Remove me'], $preview->removedScenarioTitles);
        $this->assertSame(['Brand new'], $preview->addedScenarioTitles);
        $this->assertSame(['Keep me'], $preview->matchedScenarioTitles);
        $this->assertTrue($preview->needTagMismatch);
        $this->assertSame('demo.feature', $preview->filename);
        $this->assertStringContainsString('@smoke', $preview->incomingScenarios[0]['body']);

        $codes = array_column($preview->warnings, 'code');
        $this->assertContains('title_mismatch', $codes);
        $this->assertContains('need_tag_mismatch', $codes);
        $this->assertContains('scenarios_removed', $codes);
        $this->assertContains('scenario_ids_reset', $codes);
    }

    public function test_preview_create_flags_blank_metadata(): void
    {
        $source = <<<'GHERKIN'
Feature: From file

Scenario: Only one
  Given ok
GHERKIN;

        $preview = (new FeatureImportService)->previewCreate(5, $source, 'new.feature');

        $this->assertSame('create', $preview->mode);
        $this->assertSame(5, $preview->projectId);
        $this->assertSame(['Only one'], $preview->addedScenarioTitles);

        $codes = array_column($preview->warnings, 'code');
        $this->assertContains('blank_metadata', $codes);
    }
}
