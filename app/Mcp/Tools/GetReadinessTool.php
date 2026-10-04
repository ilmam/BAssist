<?php

namespace App\Mcp\Tools;

use App\Services\ProjectInsightsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetReadinessTool extends BAssistTool
{
    protected string $name = 'get-readiness';

    protected string $title = 'Project readiness';

    protected string $description = 'Readiness check for a project: every gap on the Need Spine with its count and severity (critical, warn, info), progress per lineage level and an overall score. Use it before build, before a release, or when asked for status.';

    public function handle(Request $request, ProjectInsightsService $insights): Response|ResponseFactory
    {
        $validated = $request->validate(['project_id' => ['required', 'integer']]);

        return $this->respond(
            fn (): array => $insights->readiness($insights->project((int) $validated['project_id']))
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('Project id from list-projects.')->required(),
        ];
    }
}
