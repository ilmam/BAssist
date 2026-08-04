<?php

namespace App\Repositories;

use App\Data\FunctionalRequirementData;
use App\Data\FunctionalRequirementViewData;
use App\Models\FunctionalRequirement;
use Illuminate\Database\Eloquent\Model;

class FunctionalRequirementRepository extends BaseRepository
{
    public Model $model;

    public $editDto = FunctionalRequirementData::class;

    public $viewDto = FunctionalRequirementViewData::class;

    protected array $listFilters = [
        'project_id',
        'status_id',
        'priority_id',
        'stakeholder_need_id',
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
        $this->model = new FunctionalRequirement();
    }
}
