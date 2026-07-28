<?php

namespace App\Repositories;

use App\Data\ConstraintData;
use App\Data\ConstraintViewData;
use App\Models\Constraint;
use Illuminate\Database\Eloquent\Model;

class ConstraintRepository extends BaseRepository
{
    public Model $model;
    public $editDto = ConstraintData::class;
    public $viewDto = ConstraintViewData::class;

    public function __construct()
    {
        $this->model = new Constraint();
    }

    // Add entity-specific query methods here when the generic repository is not enough.
}
