<?php

namespace App\Repositories;

use App\Data\WorkspaceData;
use App\Data\WorkspaceViewData;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;

class WorkspaceRepository extends BaseRepository
{
    public Model $model;
    public $editDto = WorkspaceData::class;
    public $viewDto = WorkspaceViewData::class;

    protected array $listFilters = [
        'tenant_id',
        'status_id',
    ];

    protected array $listWithCounts = [
        'projects',
    ];

    public function __construct()
    {
        $this->model = new Workspace();
    }
}
