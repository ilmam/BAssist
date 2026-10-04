<?php

namespace App\Mcp\Tools;

use App\Services\ProjectInsightsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetAcceptancePlanTool extends BAssistTool
{
    protected string $name = 'get-acceptance-plan';

    protected string $title = 'Acceptance plan';

    protected string $description = 'Acceptance checks for a project: every scenario and every acceptance criterion of a functional or non-functional requirement, each with a test id, type and status. Use it to plan or verify tests.';

    public function handle(Request $request, ProjectInsightsService $insights): Response|ResponseFactory
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer'],
            'feature_id' => ['nullable', 'integer'],
            'stakeholder_need_id' => ['nullable', 'integer'],
        ]);

        return $this->respond(fn (): array => $insights->acceptancePlan(
            $insights->project((int) $validated['project_id']),
            array_filter([
                'feature_id' => $validated['feature_id'] ?? null,
                'stakeholder_need_id' => $validated['stakeholder_need_id'] ?? null,
            ], fn ($value) => $value !== null),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('Project id from list-projects.')->required(),
            'feature_id' => $schema->integer()->description('Only checks of this feature.'),
            'stakeholder_need_id' => $schema->integer()->description('Only checks under this stakeholder need.'),
        ];
    }
}
