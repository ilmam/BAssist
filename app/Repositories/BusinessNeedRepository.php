<?php

namespace App\Repositories;

use App\Data\BusinessNeedData;
use App\Data\BusinessNeedViewData;
use App\Models\BusinessNeed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BusinessNeedRepository extends BaseRepository
{
    public Model $model;
    public $editDto = BusinessNeedData::class;
    public $viewDto = BusinessNeedViewData::class;

    protected array $listFilters = [
        'project_id',
    ];

    protected array $listRelationFilters = [
        'business_objective_id' => 'businessObjectives',
    ];

    protected array $listContextFilters = [
        'workspace_id' => ['project', 'workspace_id'],
    ];

    protected string|array|null $listTenantScope = ['project.workspace', 'tenant_id'];

    protected array $listContextRelations = [
        'project.workspace',
    ];

    protected array $listWithCounts = [
        'businessObjectives',
    ];

    public function __construct()
    {
        $this->model = new BusinessNeed();
    }

    /**
     * Apex needs that have no measurable "what" (objectives) yet.
     */
    protected function applyOrphanConstraint(Builder $query): void
    {
        $query->whereDoesntHave('businessObjectives');
    }

    protected function isOrphan(Model $model): bool
    {
        return (int) ($model->getAttribute('business_objectives_count') ?? 0) === 0;
    }
}
