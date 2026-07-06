<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

/**
 * Base data access layer for entities. HTTP routes must never touch models
 * directly; controllers resolve a repository and call methods on it.
 */
class BaseRepository
{
    use \App\Traits\DataHelperTrait;

    public Model $model;
    public $editDto;
    public $viewDto;

    public function getSelectOptions($fields = null)
    {
        if ($fields == null) {
            $fields = $this->model->getListFields();
        }

        return $this->model::pluck(...$fields);
    }

    public function getAll()
    {
        return $this->viewDto::collect(
            $this->getCollectionWithBelongsTo($all = true)
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

    protected function getCollectionWithBelongsTo(bool $all = true, $id = null)
    {
        $relations = $this->model->getAllRelations();

        if ($all) {
            $dataCollection = $this->model::all();
        } elseif ($id === null) {
            $dataCollection = $this->model::first();
        } else {
            $dataCollection = $this->model::findOrFail($id);
        }

        if (isset($relations['BelongsTo'])) {
            foreach ($relations['BelongsTo'] as $relation) {
                if ($dataCollection instanceof Model) {
                    $dataCollection->load($relation);
                } else {
                    $dataCollection = $dataCollection->load($relation);
                }
            }
        }

        return $dataCollection;
    }
}
