<?php

namespace App\Repositories;

use App\Data\AssumptionData;
use App\Data\AssumptionViewData;
use App\Models\Assumption;
use Illuminate\Database\Eloquent\Model;

class AssumptionRepository extends BaseRepository
{
    public Model $model;
    public $editDto = AssumptionData::class;
    public $viewDto = AssumptionViewData::class;

    public function __construct()
    {
        $this->model = new Assumption();
    }

    // Add entity-specific query methods here when the generic repository is not enough.
}
