<?php

namespace App\Repositories;

use App\Data\NonFunctionalRequirementData;
use App\Data\NonFunctionalRequirementViewData;
use App\Models\NonFunctionalRequirement;
use App\Support\SolutionPackagingParent;
use Illuminate\Database\Eloquent\Model;

class NonFunctionalRequirementRepository extends BaseRepository
{
    public Model $model;

    public $editDto = NonFunctionalRequirementData::class;

    public $viewDto = NonFunctionalRequirementViewData::class;

    protected array $listFilters = [
        'project_id',
        'status_id',
        'priority_id',
        'stakeholder_need_id',
        'change_request_id',
        'category',
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
        $this->model = new NonFunctionalRequirement();
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
