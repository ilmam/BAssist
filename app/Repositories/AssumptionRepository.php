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

    protected array $listFilters = [
        'project_id',
        'status',
    ];

    protected array $listContextFilters = [
        'workspace_id' => ['project', 'workspace_id'],
    ];

    protected string|array|null $listTenantScope = ['project.workspace', 'tenant_id'];

    protected array $listContextRelations = [
        'project.workspace',
    ];

    public function __construct()
    {
        $this->model = new Assumption();
    }
}
