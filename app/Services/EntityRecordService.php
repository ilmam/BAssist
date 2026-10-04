<?php

namespace App\Services;

use App\Repositories\BaseRepository;
use App\Support\CrudEntityRegistry;
use App\Support\DtoMetadata;
use App\Support\EntityAccess;
use App\Support\Validation\EntityValidator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Entity CRUD for callers that address an entity by name at runtime (the MCP
 * tools). It follows the same path as Api\CrudController: role permission,
 * the entity's edit DTO for validation, the tenant payload guard, then the
 * repository. See docs/mcp.md.
 */
class EntityRecordService
{
    /** Never offered through this service, whatever the caller's role. */
    public const HIDDEN = ['Tenant'];

    public const DEFAULT_LIMIT = 50;

    public const MAX_LIMIT = 200;

    /** Long text is cut in list results; get one record for the full value. */
    protected const LIST_TEXT_LIMIT = 300;

    /**
     * Entities the caller may at least view, with what they may do to each.
     *
     * @return list<array{entity: string, resource: string, can: list<string>}>
     */
    public function entities(): array
    {
        $user = auth()->user();
        $result = [];

        foreach (array_keys(CrudEntityRegistry::all()) as $model) {
            if (in_array($model, self::HIDDEN, true) || ! EntityAccess::can($user, $model, EntityAccess::VIEW)) {
                continue;
            }

            $result[] = [
                'entity' => $model,
                'resource' => CrudEntityRegistry::resourceName($model),
                'can' => array_values(array_filter(
                    [EntityAccess::VIEW, EntityAccess::CREATE, EntityAccess::UPDATE, EntityAccess::DELETE],
                    fn (string $ability) => EntityAccess::can($user, $model, $ability),
                )),
            ];
        }

        return $result;
    }

    /**
     * Fields a create / update accepts, with their validation rules, and the
     * filters a list accepts.
     *
     * @return array<string, mixed>
     */
    public function describe(string $entity): array
    {
        $model = $this->resolve($entity);
        EntityAccess::authorize(auth()->user(), $model, EntityAccess::VIEW);

        $repository = CrudEntityRegistry::repository($model);
        $rules = EntityValidator::rulesFor($repository->editDto);
        $fields = [];

        foreach (DtoMetadata::for($repository->editDto)->formFields() as $name => $args) {
            if (! empty($args['readonly']) || ($args[0] ?? null) === 'attachments') {
                continue;
            }

            $fields[] = array_filter([
                'name' => $name,
                'type' => $args[0] ?? 'text',
                'references' => isset($args[1]) && is_string($args[1]) ? $args[1] : null,
                'help' => $args['help'] ?? null,
                'rules' => array_map(EntityValidator::describeRule(...), $rules[$name] ?? []),
            ], fn ($value) => $value !== null);
        }

        return [
            'entity' => $model,
            'resource' => CrudEntityRegistry::resourceName($model),
            'fields' => $fields,
            'list_filters' => $repository->allowedListFilters(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters  only the entity's allowed list filters are used
     * @return array{entity: string, total: int, returned: int, records: list<array<string, mixed>>}
     */
    public function list(string $entity, array $filters = [], ?string $search = null, ?int $limit = null): array
    {
        $model = $this->resolve($entity);
        EntityAccess::authorize(auth()->user(), $model, EntityAccess::VIEW);

        $repository = CrudEntityRegistry::repository($model);
        $filters = array_filter(
            array_intersect_key($filters, array_flip($repository->allowedListFilters())),
            fn ($value) => $value !== null && $value !== '',
        );

        $records = collect($repository->getAll($filters))
            ->map(fn ($row) => $this->compact($this->toArray($row)));

        if ($search !== null && trim($search) !== '') {
            $needle = Str::lower(trim($search));
            $records = $records->filter(function (array $row) use ($needle): bool {
                foreach (['code', 'title', 'name'] as $key) {
                    if (isset($row[$key]) && is_string($row[$key]) && str_contains(Str::lower($row[$key]), $needle)) {
                        return true;
                    }
                }

                return false;
            });
        }

        $limit = max(1, min($limit ?? self::DEFAULT_LIMIT, self::MAX_LIMIT));

        return [
            'entity' => $model,
            'total' => $records->count(),
            'returned' => min($records->count(), $limit),
            'records' => $records->take($limit)->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function show(string $entity, int $id): array
    {
        $model = $this->resolve($entity);
        EntityAccess::authorize(auth()->user(), $model, EntityAccess::VIEW);

        return ['entity' => $model] + $this->toArray(CrudEntityRegistry::repository($model)->getById($id));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(string $entity, array $data): array
    {
        $model = $this->resolve($entity);
        EntityAccess::authorize(auth()->user(), $model, EntityAccess::CREATE);

        $repository = CrudEntityRegistry::repository($model);
        $payload = $this->validated($repository, $data);
        $created = $repository->create($payload);

        return $this->reference($model, $created);
    }

    /**
     * Partial update: fields that are not sent keep their current value.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $entity, int $id, array $data): array
    {
        $model = $this->resolve($entity);
        EntityAccess::authorize(auth()->user(), $model, EntityAccess::UPDATE);

        $repository = CrudEntityRegistry::repository($model);
        $current = $this->toArray($repository->editById($id));
        unset($data['id']);

        $repository->update($id, $this->validated($repository, array_merge($current, $data)));

        return $this->reference($model, $repository->findModel($id));
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $entity, int $id): array
    {
        $model = $this->resolve($entity);
        EntityAccess::authorize(auth()->user(), $model, EntityAccess::DELETE);

        $repository = CrudEntityRegistry::repository($model);
        $reference = $this->reference($model, $repository->findModel($id));
        $repository->delete($id);

        return $reference + ['deleted' => true];
    }

    /**
     * Accepts the model name (StakeholderNeed) or the resource name (stakeholder_needs).
     */
    public function resolve(string $entity): string
    {
        $entity = trim($entity);
        $model = array_key_exists($entity, CrudEntityRegistry::all())
            ? $entity
            : CrudEntityRegistry::modelFromResource(Str::snake($entity));

        if ($model === null || in_array($model, self::HIDDEN, true)) {
            throw new NotFoundHttpException("Unknown entity [{$entity}].");
        }

        return $model;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function validated(BaseRepository $repository, array $data): array
    {
        $payload = EntityValidator::validate($repository->editDto, $data)->toArray();
        $repository->assertPayloadInTenant($payload);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    protected function reference(string $model, mixed $record): array
    {
        $id = $record instanceof Model ? $record->getKey() : ($record->id ?? null);
        $code = $record instanceof Model ? $record->getAttribute('code') : ($record->code ?? null);
        $title = $record instanceof Model
            ? ($record->getAttribute('title') ?? $record->getAttribute('name'))
            : ($record->title ?? $record->name ?? null);

        return array_filter([
            'entity' => $model,
            'id' => $id !== null ? (int) $id : null,
            'code' => $code,
            'title' => $title,
            'url' => $id !== null ? model_route($model, 'show', $id) : null,
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    protected function toArray(mixed $row): array
    {
        return match (true) {
            is_array($row) => $row,
            is_object($row) && method_exists($row, 'toArray') => $row->toArray(),
            default => (array) $row,
        };
    }

    /**
     * Keep a list row small: scalar fields only, long text shortened.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function compact(array $row): array
    {
        $result = [];

        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $result[$key] = Str::limit($value, self::LIST_TEXT_LIMIT);
            } elseif ($value === null || is_scalar($value)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
