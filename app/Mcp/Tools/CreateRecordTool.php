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
#[IsDestructive(false)]
class CreateRecordTool extends BAssistTool
{
    protected string $name = 'create-record';

    protected string $title = 'Create a record';

    protected string $description = 'Create one record of an entity. data holds the fields from describe-entity; project_id is required for almost every entity, and parent links (for example stakeholder_need_id on a Feature) are how lineage is built. Returns the new id, code and a link to the record. Only create what the user has agreed to; record anything unknown as an Assumption instead of inventing it.';

    public function handle(Request $request, EntityRecordService $records): Response|ResponseFactory
    {
        $this->requireWrite($request);

        $validated = $request->validate([
            'entity' => ['required', 'string', 'max:100'],
            'data' => ['required', 'array'],
        ]);

        return $this->respond(fn (): array => $records->create($validated['entity'], $validated['data']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entity' => $schema->string()->description('Entity name, for example StakeholderNeed.')->required(),
            'data' => $schema->object()->description('Field values, for example {"project_id": 7, "title": "Dealer can see part availability"}.')->required(),
        ];
    }
}
