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
        $this->model = new BusinessRule();
    }
}
