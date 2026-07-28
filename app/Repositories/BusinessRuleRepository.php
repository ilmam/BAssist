<?php

namespace App\Repositories;

use App\Data\BusinessRuleData;
use App\Data\BusinessRuleViewData;
use App\Models\BusinessRule;
use Illuminate\Database\Eloquent\Model;

class BusinessRuleRepository extends BaseRepository
{
    public Model $model;
    public $editDto = BusinessRuleData::class;
    public $viewDto = BusinessRuleViewData::class;

    public function __construct()
    {
        $this->model = new BusinessRule();
    }

    // Add entity-specific query methods here when the generic repository is not enough.
}
