<?php

namespace App\Repositories;

use App\Data\StatusData;
use App\Data\StatusViewData;
use App\Models\Status;
use Illuminate\Database\Eloquent\Model;

class StatusRepository extends BaseRepository
{
    public Model $model;
    public $editDto = StatusData::class;
    public $viewDto = StatusViewData::class;

    public function __construct()
    {
        $this->model = new Status();
    }

    public function getSelectOptions($fields = null)
    {
        return $this->model::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck(...($fields ?? $this->model->getListFields()));
    }
}
