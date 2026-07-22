<?php

namespace App\Repositories;

use App\Helpers\ListUi;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Base data access layer for entities. HTTP routes must never touch models
 * directly; controllers resolve a repository and call methods on it.
 */
class BaseRepository
{
    public Model $model;
    public $editDto;
    public $viewDto;

    /**
     * Direct column filters accepted on list/index requests (e.g. project_id).
     *
     * @var list<string>
     */
    protected array $listFilters = [];

    /**
     * BelongsToMany / relation filters: request key => relation method name.
     *
     * @var array<string, string>
     */
    protected array $listRelationFilters = [];

    /**
     * Ancestor context filters applied via whereHas when the model has no column.
     * Example: 'workspace_id' => ['project', 'workspace_id']
     *
     * @var array<string, array{0: string, 1: string}>
     */
    protected array $listContextFilters = [];

    /**
     * Implicit tenant scope for list queries (never from request/URL).
     * null = no tenant scope; string = direct column (e.g. 'tenant_id');
     * [relation, column] = whereHas path (e.g. ['workspace', 'tenant_id']).
     *
     * @var string|array{0: string, 1: string}|null
     */
    protected string|array|null $listTenantScope = null;

    /**
     * Relations to withCount() on list queries (exposed as {relation}_count).
     *
     * @var list<string>
     */
    protected array $listWithCounts = [];

    /**
     * Nested BelongsTo paths to eager-load so parent context IDs can be copied onto rows.
     *
     * @var list<string>
     */
    protected array $listContextRelations = [];

    public function getSelectOptions($fields = null)
    {
        if ($fields == null) {
            $fields = $this->model->getListFields();
        }

        return $this->model::pluck(...$fields);
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    public function getAll(?array $filters = null)
    {
        return $this->viewDto::collect(
            $this->getCollectionWithBelongsTo(true, null, $filters)
        );
    }

    public function getFirst()
    {
        return $this->viewDto::from($this->viewDto::empty());
    }

    public function getById($Id)
    {
        return $this->viewDto::from(
            $this->getCollectionWithBelongsTo(false, $Id)
        );
    }

    public function editById($Id)
    {
        return $this->editDto::from(
            $this->model::findOrFail($Id)
        );
    }

    public function delete($Id)
    {
        $this->model::destroy($Id);
    }

    public function create(array $data)
    {
        return $this->model::create($data);
    }

    public function update($id, array $newData)
    {
        return $this->model::whereId($id)->update($newData);
    }

    /**
     * @return list<string>
     */
    public function allowedListFilters(): array
    {
        return array_values(array_unique(array_merge(
            $this->listFilters,
            array_keys($this->listRelationFilters),
            array_keys($this->listContextFilters),
            ListUi::CONTEXT_KEYS,
            ['orphans']
        )));
    }

    /**
     * Whether list queries honor workspace_id (direct column or ancestor context).
     */
    public function usesWorkspaceListScope(): bool
    {
        return in_array('workspace_id', $this->listFilters, true)
            || array_key_exists('workspace_id', $this->listContextFilters);
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    protected function getCollectionWithBelongsTo(bool $all = true, $id = null, ?array $filters = null)
    {
        $relations = $this->model->getAllRelations();

        if ($all) {
            $query = $this->newListQuery($filters);
            $dataCollection = $query->get();
        } elseif ($id === null) {
            $dataCollection = $this->model::first();
        } else {
            $dataCollection = $this->model::findOrFail($id);
        }

        $belongsTo = $relations['BelongsTo'] ?? [];
        $eager = array_values(array_unique(array_merge($belongsTo, $this->listContextRelations)));

        if ($eager !== []) {
            if ($dataCollection instanceof Model) {
                $dataCollection->load($eager);
            } elseif ($dataCollection instanceof Collection) {
                $dataCollection->load($eager);
            }
        }

        if ($all && $dataCollection instanceof Collection) {
            $this->enrichListCollection($dataCollection);
        }

        return $dataCollection;
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    protected function newListQuery(?array $filters = null): Builder
    {
        $query = $this->model::query();

        if ($this->listWithCounts !== []) {
            $query->withCount($this->listWithCounts);
        }

        $this->applyImplicitTenantScope($query);

        $filters = $filters ?? [];

        foreach ($this->listFilters as $column) {
            if (! array_key_exists($column, $filters) || $filters[$column] === null || $filters[$column] === '') {
                continue;
            }

            $query->where($column, $filters[$column]);
        }

        foreach ($this->listRelationFilters as $param => $relation) {
            if (! array_key_exists($param, $filters) || $filters[$param] === null || $filters[$param] === '') {
                continue;
            }

            $value = $filters[$param];
            $query->whereHas($relation, function (Builder $relationQuery) use ($value) {
                $relationQuery->where(
                    $relationQuery->getModel()->getQualifiedKeyName(),
                    $value
                );
            });
        }

        foreach ($this->listContextFilters as $param => $path) {
            if (! array_key_exists($param, $filters) || $filters[$param] === null || $filters[$param] === '') {
                continue;
            }

            // Skip when the same key is already applied as a direct column filter.
            if (in_array($param, $this->listFilters, true)) {
                continue;
            }

            [$relation, $column] = $path;
            $value = $filters[$param];
            $query->whereHas($relation, function (Builder $relationQuery) use ($column, $value) {
                $relationQuery->where($column, $value);
            });
        }

        if ($this->wantsOrphansOnly($filters)) {
            $this->applyOrphanConstraint($query);
        }

        return $query;
    }

    /**
     * Scope list queries to the authenticated user's tenant.
     * Tenant is fixed by auth/provisioning — never taken from request filters.
     */
    protected function applyImplicitTenantScope(Builder $query): void
    {
        if ($this->listTenantScope === null) {
            return;
        }

        $tenantId = auth()->user()?->tenant_id;
        if ($tenantId === null) {
            $query->whereRaw('0 = 1');

            return;
        }

        if (is_string($this->listTenantScope)) {
            $query->where($this->listTenantScope, $tenantId);

            return;
        }

        [$relation, $column] = $this->listTenantScope;
        $query->whereHas($relation, function (Builder $relationQuery) use ($column, $tenantId) {
            $relationQuery->where($column, $tenantId);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function wantsOrphansOnly(array $filters): bool
    {
        if (! array_key_exists('orphans', $filters)) {
            return false;
        }

        $value = $filters['orphans'];

        return $value === true
            || $value === 1
            || $value === '1'
            || $value === 'true';
    }

    /**
     * Override in entity repositories that support orphan highlighting.
     */
    protected function applyOrphanConstraint(Builder $query): void
    {
        //
    }

    /**
     * @param  Collection<int, Model>  $collection
     */
    protected function enrichListCollection(Collection $collection): void
    {
        $collection->each(function (Model $model): void {
            $this->attachParentContextIds($model);
            $model->setAttribute('is_orphan', $this->isOrphan($model));
        });
    }

    /**
     * Copy workspace/project ids onto the row for sticky child-link URLs.
     * Tenant is not attached — it is implicit from the authenticated user.
     */
    protected function attachParentContextIds(Model $model): void
    {
        if ($model instanceof Project || $model instanceof Workspace) {
            return;
        }

        if ($model->relationLoaded('project') && $model->project) {
            /** @var Project $project */
            $project = $model->project;
            $model->setAttribute('project_id', $project->id);
            $model->setAttribute('workspace_id', $project->workspace_id);
        }
    }

    protected function isOrphan(Model $model): bool
    {
        return false;
    }
}
