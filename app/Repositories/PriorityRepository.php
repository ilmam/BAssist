<?php

namespace App\Repositories;

use App\Data\PriorityData;
use App\Data\PriorityViewData;
use App\Models\Priority;
use Illuminate\Database\Eloquent\Model;

class PriorityRepository extends BaseRepository
{
    public Model $model;
    public $editDto = PriorityData::class;
    public $viewDto = PriorityViewData::class;

    public function __construct()
    {
        $this->model = new Priority();
    }

    public function getSelectOptions($fields = null)
    {
        return $this->model::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck(...($fields ?? $this->model->getListFields()));
    }
}
