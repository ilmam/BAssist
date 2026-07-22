<?php

namespace App\Repositories;

use App\Data\StatusData;
use App\Data\StatusViewData;
use App\Models\Status;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class StatusRepository extends BaseRepository
{
    public Model $model;
    public $editDto = StatusData::class;
    public $viewDto = StatusViewData::class;

    public function __construct()
    {
        $this->model = new Status();
    }

    public function create(array $data)
    {
        $data['is_system'] = false;

        return $this->model::create($data);
    }

    public function update($id, array $newData)
    {
        /** @var Status $status */
        $status = $this->model::findOrFail($id);

        if ($status->is_system) {
            // System statuses keep locked identity (code / is_system); allow label edits.
            $newData = array_intersect_key($newData, array_flip([
                'name',
                'description',
                'sort_order',
            ]));
        } else {
            unset($newData['is_system']);
        }

        $status->update($newData);

        return $status;
    }

    public function delete($Id)
    {
        /** @var Status $status */
        $status = $this->model::findOrFail($Id);

        if ($status->is_system) {
            throw new RuntimeException('System statuses cannot be deleted.');
        }

        $status->delete();
    }

    public function getSelectOptions($fields = null)
    {
        return $this->model::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck(...($fields ?? $this->model->getListFields()));
    }
}
