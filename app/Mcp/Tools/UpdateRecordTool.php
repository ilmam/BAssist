<?php

namespace App\Mcp\Tools;

use App\Services\EntityRecordService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsIdempotent]
class UpdateRecordTool extends BAssistTool
{
    protected string $name = 'update-record';

    protected string $title = 'Update a record';

    protected string $description = 'Change fields of one record. Send only the fields to change in data; every other field keeps its value. Editing the content of an approved record resets its approval. The change is recorded in the record history under the user, marked as made by an AI assistant.';

    public function handle(Request $request, EntityRecordService $records): Response|ResponseFactory
    {
        $this->requireWrite($request);

        $validated = $request->validate([
            'entity' => ['required', 'string', 'max:100'],
            'id' => ['required', 'integer'],
            'data' => ['required', 'array', 'min:1'],
        ]);

        return $this->respond(
            fn (): array => $records->update($validated['entity'], (int) $validated['id'], $validated['data'])
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entity' => $schema->string()->description('Entity name, for example FunctionalRequirement.')->required(),
            'id' => $schema->integer()->description('Record id.')->required(),
            'data' => $schema->object()->description('Only the fields to change, for example {"acceptance_criteria": "..."}.')->required(),
        ];
    }
}
