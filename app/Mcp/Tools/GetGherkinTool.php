<?php

namespace App\Mcp\Tools;

use App\Services\ProjectInsightsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetGherkinTool extends BAssistTool
{
    protected string $name = 'get-gherkin';

    protected string $title = 'Gherkin feature files';

    protected string $description = 'Assembled Gherkin (.feature) documents with a suggested filename each. Give feature_id for one feature, or project_id for every feature of a project. Use it to write feature files or tests into a codebase.';

    public function handle(Request $request, ProjectInsightsService $insights): Response|ResponseFactory
    {
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer', 'required_without:feature_id'],
            'feature_id' => ['nullable', 'integer', 'required_without:project_id'],
        ]);

        return $this->respond(function () use ($validated, $insights): array {
            if (! empty($validated['feature_id'])) {
                return ['features' => [$insights->featureGherkin((int) $validated['feature_id'])]];
            }

            return $insights->projectGherkin($insights->project((int) $validated['project_id']));
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('All features of this project.'),
            'feature_id' => $schema->integer()->description('Only this feature. Takes precedence over project_id.'),
        ];
    }
}
