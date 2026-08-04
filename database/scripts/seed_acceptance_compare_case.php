<?php

/**
 * One-off: paired FR + BDD Feature for acceptance-plan comparison.
 * Run: php database/scripts/seed_acceptance_compare_case.php
 */

use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\Scenario;
use App\Models\StakeholderNeed;
use App\Services\GherkinDocumentParser;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$project = Project::query()->where('code', 'NS-DEMO')->first()
    ?? Project::query()->orderBy('id')->first();

if ($project === null) {
    fwrite(STDERR, "No project found.\n");
    exit(1);
}

$marker = '[acceptance-compare]';

$need = StakeholderNeed::query()
    ->where('project_id', $project->id)
    ->where('title', 'like', '%'.$marker.'%')
    ->first();

if ($need === null) {
    $need = StakeholderNeed::query()->create([
        'title' => "Submit a complete parts inquiry digitally {$marker}",
        'project_id' => $project->id,
        'description' => 'As a Parts Field Agent, I want to submit a parts inquiry through a single digital form, so that procurement receives complete, validated requests without manual re-keying.',
    ]);
}

Feature::query()
    ->where('project_id', $project->id)
    ->where('title', 'like', '%'.$marker.'%')
    ->each(function (Feature $feature): void {
        $feature->scenarios()->forceDelete();
        $feature->forceDelete();
    });

FunctionalRequirement::query()
    ->where('project_id', $project->id)
    ->where('title', 'like', '%'.$marker.'%')
    ->forceDelete();

$featureBody = <<<'GHERKIN'
Feature: Parts inquiry completeness gate [acceptance-compare]
  As a Parts Field Agent
  I want inquiry status to advance only when required data is complete
  So that procurement never acts on incomplete requests
GHERKIN;

$feature = new Feature([
    'title' => 'Parts inquiry completeness gate [acceptance-compare]',
    'project_id' => $project->id,
    'stakeholder_need_id' => $need->id,
    'body' => $featureBody,
]);
$feature->syncDocumentFields(app(GherkinDocumentParser::class));
$feature->save();

$scenarios = [
    [
        'title' => 'Inquiry moves to Submitted when mandatory fields are present',
        'body' => <<<'GHERKIN'
Scenario: Inquiry moves to Submitted when mandatory fields are present
  Given a dealer inquiry draft with part number, quantity, and dealer code filled
  When the agent submits the inquiry
  Then the inquiry status is Submitted
  And procurement can see it in the open queue
GHERKIN,
    ],
    [
        'title' => 'Inquiry stays Draft when part number is missing',
        'body' => <<<'GHERKIN'
@edge-case
Scenario: Inquiry stays Draft when part number is missing
  Given a dealer inquiry draft missing the part number
  When the agent attempts to submit the inquiry
  Then the inquiry remains Draft
  And the agent is told which fields are still required
GHERKIN,
    ],
    [
        'title' => 'Inquiry stays Draft when quantity is not a positive integer',
        'body' => <<<'GHERKIN'
@edge-case
Scenario: Inquiry stays Draft when quantity is not a positive integer
  Given a dealer inquiry draft with quantity "0"
  When the agent attempts to submit the inquiry
  Then the inquiry remains Draft
  And the agent is told that quantity must be a positive integer
GHERKIN,
    ],
];

foreach ($scenarios as $scenarioData) {
    Scenario::query()->create([
        'feature_id' => $feature->id,
        'title' => $scenarioData['title'],
        'body' => $scenarioData['body'],
    ]);
}

$fr = FunctionalRequirement::query()->create([
    'title' => 'Parts inquiry completeness gate [acceptance-compare]',
    'project_id' => $project->id,
    'stakeholder_need_id' => $need->id,
    'statement' => 'The system shall accept a parts inquiry for procurement only when part number, quantity, and dealer code are present and valid; otherwise the inquiry shall remain Draft and the missing or invalid fields shall be reported to the agent.',
    'trigger' => 'When a Parts Field Agent submits a dealer parts inquiry',
    'acceptance_criteria' => <<<'TXT'
- Inquiry moves to Submitted when part number, quantity, and dealer code are present
- Inquiry stays Draft when part number is missing
- Edge: Inquiry stays Draft when quantity is not a positive integer
TXT,
]);

echo "Project: {$project->code} ({$project->id})\n";
echo "Stakeholder Need: {$need->code} — {$need->title}\n";
echo "Feature: {$feature->code} — {$feature->title}\n";
echo "Scenarios: ".count($scenarios)."\n";
echo "FR: {$fr->code} — {$fr->title}\n";
echo "Open: /acceptance-plan?project_id={$project->id}\n";
