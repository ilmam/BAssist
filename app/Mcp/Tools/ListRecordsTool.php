<?php

namespace App\Mcp\Tools;

use App\Services\EntityRecordService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListRecordsTool extends BAssistTool
{
    protected string $name = 'list-records';

    protected string $title = 'List records';

    protected string $description = 'List records of one entity (for example BusinessNeed, StakeholderNeed, Feature, Assumption, Risk, Status, Priority). Pass project_id to stay inside one project. Long text is shortened; use get-record for the full record. Use Status and Priority to look up the ids those fields expect.';

    public function handle(Request $request, EntityRecordService $records): Response|ResponseFactory
    {
        $validated = $request->validate([
            'entity' => ['required', 'string', 'max:100'],
            'project_id' => ['nullable', 'integer'],
            'filters' => ['nullable', 'array'],
            'search' => ['nullable', 'string', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.EntityRecordService::MAX_LIMIT],
        ]);

        $filters = $validated['filters'] ?? [];
        if (isset($validated['project_id'])) {
            $filters['project_id'] = $validated['project_id'];
        }

        return $this->respond(fn (): array => $records->list(
            $validated['entity'],
            $filters,
            $validated['search'] ?? null,
            $validated['limit'] ?? null,
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entity' => $schema->string()->description('Entity name, for example StakeholderNeed.')->required(),
            'project_id' => $schema->integer()->description('Only records of this project.'),
            'filters' => $schema->object()->description('Other filters, for example {"status_id": 2, "stakeholder_need_id": 14}. describe-entity lists the filters each entity accepts.'),
            'search' => $schema->string()->description('Keep records whose code, title or name contains this text.'),
            'limit' => $schema->integer()->description('Maximum records to return (default '.EntityRecordService::DEFAULT_LIMIT.', maximum '.EntityRecordService::MAX_LIMIT.').'),
        ];
    }
}
