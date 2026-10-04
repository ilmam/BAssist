<?php

namespace App\Mcp\Tools;

use App\Services\EntityRecordService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly(false)]
#[IsDestructive]
class DeleteRecordTool extends BAssistTool
{
    protected string $name = 'delete-record';

    protected string $title = 'Delete a record';

    protected string $description = 'Delete one record. Only when the user has explicitly asked for this record to be deleted. To retire a requirement while keeping its history, set its status to deprecated with update-record instead.';

    public function handle(Request $request, EntityRecordService $records): Response|ResponseFactory
    {
        $this->requireWrite($request);

        $validated = $request->validate([
            'entity' => ['required', 'string', 'max:100'],
            'id' => ['required', 'integer'],
        ]);

        return $this->respond(fn (): array => $records->delete($validated['entity'], (int) $validated['id']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entity' => $schema->string()->description('Entity name.')->required(),
            'id' => $schema->integer()->description('Record id.')->required(),
        ];
    }
}
