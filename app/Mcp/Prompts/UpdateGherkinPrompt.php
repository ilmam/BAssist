<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

/**
 * A client lists this as a command (Claude Code: /mcp__bassist__update-gherkin).
 * It hands the assistant the steps to bring Features and Scenarios in line with
 * the other functional specifications; what it saves is ordinary Feature and
 * Scenario records, so the usual permissions, validation and history apply.
 */
class UpdateGherkinPrompt extends Prompt
{
    protected string $name = 'update-gherkin';

    protected string $title = 'Update Gherkin from requirements';

    protected string $description = 'Review the Features and Scenarios (Gherkin) of a project against its functional requirements, business rules, process flows and UI designs, then add, change or flag scenarios so the acceptance covers what the specification says.';

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('project', 'Project name or code, for example "Demo Parts Digital Intake".', required: true),
            new Argument('scope', 'Optional: a feature title, or a functional requirement code or title, to limit the review. Leave empty to review the whole project.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $project = trim((string) $request->get('project'));
        $scope = trim((string) $request->get('scope'));

        $limit = $scope !== ''
            ? "Limit the review to \"{$scope}\": the feature with that title, or the feature(s) that cover the functional requirement with that code or title."
            : 'Review every feature of the project.';

        return Response::text(<<<TEXT
            Bring the Gherkin (Features and Scenarios) of a BAssist project in line with its functional specification.

            1. Call list-projects and find the project "{$project}". {$limit}
            2. Read the specification the scenarios must follow: list-records then get-record for FunctionalRequirement, NonFunctionalRequirement, BusinessRule, Constraint and, where the project has them, Screen (read its salt) and the process flow (get-process-flow). Note each requirement's status and its stakeholder need.
            3. Read the current acceptance: get-gherkin (project_id, or feature_id) and get-traceability for the project. Note which requirements have no feature, which features have no scenarios, and which scenarios mention behaviour no requirement states.
            4. Call list-comments (state active) so you do not undo a decision still under discussion. If a thread on a record you are about to change is open or answered, follow its latest reply (answered threads written by the signed-in user) or leave the record alone.
            5. Compare, requirement by requirement, and decide for each gap:
               - A requirement, rule or screen element with no scenario: add a Scenario under the feature that covers it (create-record, entity Scenario). If no feature covers it, create a Feature first and link it to the requirement's stakeholder need.
               - A scenario that contradicts or no longer matches the specification: update-record on that Scenario (or Feature) so its Given/When/Then states the current behaviour. Keep its id and title where the intent is unchanged.
               - A scenario that tests behaviour nothing in the specification states: do not delete it. Raise it with add-comment on the scenario.
               - A requirement that is silent, vague or inconsistent with another: do not guess. Raise the finding with add-comment on that requirement, and write no scenario for the unclear part.
            6. Before the first write on a kind of record, call describe-entity for Feature and Scenario and follow their fields exactly. Write each step in the business language of the specification, one behaviour per scenario, with concrete values only where the specification gives them. Never invent business facts; an unknown is an Assumption with status open.
            7. Do not change a feature's or scenario's status, and do not touch records of other projects.
            8. Call get-gherkin again for what you changed and check it reads as valid Gherkin. Report: scenarios added, scenarios changed (with the reason and the requirement behind each), comments raised, and anything you left alone and why.
            TEXT);
    }
}
