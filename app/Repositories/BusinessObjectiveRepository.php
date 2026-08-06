<?php

namespace App\Repositories;

use App\Data\BusinessObjectiveData;
use App\Data\BusinessObjectiveViewData;
use App\Models\BusinessObjective;
use Illuminate\Database\Eloquent\Model;

class BusinessObjectiveRepository extends BaseRepository
{
    public Model $model;
    public $editDto = BusinessObjectiveData::class;
    public $viewDto = BusinessObjectiveViewData::class;

    protected array $listFilters = [
        'project_id',
    ];

    protected array $listRelationFilters = [
        'business_need_id' => 'businessNeeds',
    ];

    protected array $listContextFilters = [
        'workspace_id' => ['project', 'workspace_id'],
    ];

    protected string|array|null $listTenantScope = ['project.workspace', 'tenant_id'];

    protected array $listContextRelations = [
        'project.workspace',
    ];

    protected array $listWithCounts = [
        'businessNeeds',
    ];

    public function __construct()
    {
        $this->model = new BusinessObjective();
    }
}
