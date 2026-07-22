<?php

namespace App\Repositories;

use App\Data\TenantData;
use App\Data\TenantViewData;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

class TenantRepository extends BaseRepository
{
    public Model $model;
    public $editDto = TenantData::class;
    public $viewDto = TenantViewData::class;

    public function __construct()
    {
        $this->model = new Tenant();
    }

    // Add entity-specific query methods here when the generic repository is not enough.
}
