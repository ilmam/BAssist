<?php

namespace App\Mcp\Tools;

use App\Services\ProjectInsightsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetLineageTool extends BAssistTool
{
    protected string $name = 'get-lineage';

    protected string $title = 'Lineage of a record';

    protected string $description = 'Where one record sits on the Need Spine: its parents, its children, its local gaps, the state of each of the five lineage levels and the suggested next step. Works for BusinessNeed, BusinessObjective, StakeholderNeed, Feature, FunctionalRequirement, NonFunctionalRequirement and Scenario. Use it to check a record is ready before building it.';

    public function handle(Request $request, ProjectInsightsService $insights): Response|ResponseFactory
    {
        $validated = $request->validate([
            'entity' => ['required', 'string', 'max:100'],
            'id' => ['required', 'integer'],
        ]);

        return $this->respond(
            fn (): array => $insights->lineage($validated['entity'], (int) $validated['id'])
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entity' => $schema->string()->description('Entity name, for example Feature or FunctionalRequirement.')->required(),
            'id' => $schema->integer()->description('Record id.')->required(),
        ];
    }
}
