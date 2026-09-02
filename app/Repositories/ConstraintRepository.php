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
        $this->model = new Constraint();
    }
}
