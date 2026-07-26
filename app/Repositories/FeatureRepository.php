<?php

namespace App\Repositories;

use App\Data\FeatureData;
use App\Data\FeatureViewData;
use App\Models\Feature;
use Illuminate\Database\Eloquent\Model;

class FeatureRepository extends BaseRepository
{
    public Model $model;
    public $editDto = FeatureData::class;
    public $viewDto = FeatureViewData::class;

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

    protected array $listWithCounts = [
        'scenarios',
    ];

    public function __construct()
    {
        $this->model = new Feature();
    }

    public function create(array $data)
    {
        $feature = new Feature($this->filterFillable($data));
        $feature->syncDocumentFields();
        $feature->save();

        return $feature->fresh();
    }

    public function update($id, array $newData)
    {
        /** @var Feature $feature */
        $feature = Feature::query()->findOrFail($id);
        $feature->fill($this->filterFillable($newData));
        $feature->syncDocumentFields();
        $feature->save();

        return $feature->fresh();
    }
}
