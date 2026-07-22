<?php

namespace App\Repositories;

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
     * Relations to withCount() on list queries (exposed as {relation}_count).
     *
     * @var list<string>
     */
    protected array $listWithCounts = [];

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
            ['orphans']
        )));
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
            $this->enrichListCollection($dataCollection);
        } elseif ($id === null) {
            $dataCollection = $this->model::first();
        } else {
            $dataCollection = $this->model::findOrFail($id);
        }

        if (isset($relations['BelongsTo'])) {
            foreach ($relations['BelongsTo'] as $relation) {
                if ($dataCollection instanceof Model) {
                    $dataCollection->load($relation);
                } elseif ($dataCollection instanceof Collection) {
                    $dataCollection->load($relation);
                }
            }
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

        if ($this->wantsOrphansOnly($filters)) {
            $this->applyOrphanConstraint($query);
        }

        return $query;
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
            $model->setAttribute('is_orphan', $this->isOrphan($model));
        });
    }

    protected function isOrphan(Model $model): bool
    {
        return false;
    }
}
