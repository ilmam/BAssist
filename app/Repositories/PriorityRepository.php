<?php

namespace App\Repositories;

use App\Data\PriorityData;
use App\Data\PriorityViewData;
use App\Models\Priority;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class PriorityRepository extends BaseRepository
{
    public Model $model;
    public $editDto = PriorityData::class;
    public $viewDto = PriorityViewData::class;

    public function __construct()
    {
        $this->model = new Priority();
    }

    public function create(array $data)
    {
        $data['is_system'] = false;

        return $this->model::create($data);
    }

    public function update($id, array $newData)
    {
        /** @var Priority $priority */
        $priority = $this->model::findOrFail($id);

        if ($priority->is_system) {
            // System priorities keep locked identity (code / is_system); allow label edits.
            $newData = array_intersect_key($newData, array_flip([
                'name',
                'description',
                'sort_order',
            ]));
        } else {
            unset($newData['is_system']);
        }

        $priority->update($newData);

        return $priority;
    }

    public function delete($Id)
    {
        /** @var Priority $priority */
        $priority = $this->model::findOrFail($Id);

        if ($priority->is_system) {
            throw new RuntimeException('System priorities cannot be deleted.');
        }

        $priority->delete();
    }

    public function getSelectOptions($fields = null)
    {
        return $this->model::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck(...($fields ?? $this->model->getListFields()));
    }
}
