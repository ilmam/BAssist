<?php

namespace App\Repositories;

use App\Data\FunctionalRequirementData;
use App\Data\FunctionalRequirementViewData;
use App\Models\FunctionalRequirement;
use App\Support\SolutionPackagingParent;
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
        'change_request_id',
    ];

    protected array $listContextFilters = [
        'workspace_id' => ['project', 'workspace_id'],
    ];

    protected string|array|null $listTenantScope = ['project.workspace', 'tenant_id'];

    protected array $listContextRelations = [
        'project.workspace',
        'changeRequest',
    ];

    public function __construct()
    {
        $this->model = new FunctionalRequirement();
    }

    public function create(array $data)
    {
        return parent::create(SolutionPackagingParent::normalize($data));
    }

    public function update($id, array $newData)
    {
        return parent::update($id, SolutionPackagingParent::normalize($newData));
    }
}
