<?php

namespace App\Mcp\Tools;

use App\Services\ProjectInsightsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetProcessFlowTool extends BAssistTool
{
    protected string $name = 'get-process-flow';

    protected string $title = 'Process flow (BPD)';

    protected string $description = 'One business process diagram (swimlane flow) as Mermaid swimlane text. Every node id is its process-step code (PS_2 is step PS-2), so edges and lanes are unambiguous. trace lists, per process or decision step, the stakeholder need, functional requirements and features it links to, or none. Find the id with list-records and entity SwimlaneFlow. Prefer this over get-record for diagrams.';

    public function handle(Request $request, ProjectInsightsService $insights): Response|ResponseFactory
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        return $this->respond(fn (): array => $insights->processFlow((int) $validated['id']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('SwimlaneFlow record id from list-records.')->required(),
        ];
    }
}
