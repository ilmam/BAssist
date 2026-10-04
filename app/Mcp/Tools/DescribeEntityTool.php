<?php

namespace App\Mcp\Tools;

use App\Services\EntityRecordService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class DescribeEntityTool extends BAssistTool
{
    protected string $name = 'describe-entity';

    protected string $title = 'Describe an entity';

    protected string $description = 'Without arguments: the entities the user can work with and what they may do to each. With entity: the fields create-record and update-record accept for it, their validation rules, which entity a reference field points to, and the filters list-records accepts. Call it before creating or updating a kind of record for the first time.';

    public function handle(Request $request, EntityRecordService $records): Response|ResponseFactory
    {
        $validated = $request->validate(['entity' => ['nullable', 'string', 'max:100']]);

        return $this->respond(fn (): array => empty($validated['entity'])
            ? ['entities' => $records->entities()]
            : $records->describe($validated['entity']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entity' => $schema->string()->description('Entity name, for example StakeholderNeed. Leave out to list all entities.'),
        ];
    }
}
