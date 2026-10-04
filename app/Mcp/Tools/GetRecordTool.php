<?php

namespace App\Mcp\Tools;

use App\Services\EntityRecordService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetRecordTool extends BAssistTool
{
    protected string $name = 'get-record';

    protected string $title = 'Get one record';

    protected string $description = 'The full content of one record by entity and id.';

    public function handle(Request $request, EntityRecordService $records): Response|ResponseFactory
    {
        $validated = $request->validate([
            'entity' => ['required', 'string', 'max:100'],
            'id' => ['required', 'integer'],
        ]);

        return $this->respond(fn (): array => $records->show($validated['entity'], (int) $validated['id']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entity' => $schema->string()->description('Entity name, for example Feature.')->required(),
            'id' => $schema->integer()->description('Record id.')->required(),
        ];
    }
}
