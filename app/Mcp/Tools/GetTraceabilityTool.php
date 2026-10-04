<?php

namespace App\Mcp\Tools;

use App\Services\ProjectInsightsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetTraceabilityTool extends BAssistTool
{
    protected string $name = 'get-traceability';

    protected string $title = 'Traceability matrix';

    protected string $description = 'Traceability matrix for a project: one row per trace from business need through objective and stakeholder need to feature, scenario or requirement, with the gaps on each row. Set orphans_only to see only rows with a gap. Can be large; prefer get-readiness for a summary.';

    public function handle(Request $request, ProjectInsightsService $insights): Response|ResponseFactory
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer'],
            'orphans_only' => ['nullable', 'boolean'],
            'gap' => ['nullable', 'string', 'max:100'],
        ]);

        return $this->respond(fn (): array => $insights->traceability(
            $insights->project((int) $validated['project_id']),
            array_filter([
                'orphans_only' => $validated['orphans_only'] ?? null,
                'gap' => $validated['gap'] ?? null,
            ], fn ($value) => $value !== null),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('Project id from list-projects.')->required(),
            'orphans_only' => $schema->boolean()->description('Only rows that have at least one gap.'),
            'gap' => $schema->string()->description('Only rows with this gap key (keys are listed in gap_counts).'),
        ];
    }
}
