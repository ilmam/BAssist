<?php

namespace App\Repositories;

use App\Data\ScenarioData;
use App\Data\ScenarioViewData;
use App\Models\Feature;
use App\Models\Scenario;
use Illuminate\Database\Eloquent\Model;

class ScenarioRepository extends BaseRepository
{
    public Model $model;
    public $editDto = ScenarioData::class;
    public $viewDto = ScenarioViewData::class;

    protected array $listFilters = [
        'feature_id',
        'status_id',
    ];

    protected array $listContextFilters = [
        'project_id' => ['feature', 'project_id'],
        'workspace_id' => ['feature.project', 'workspace_id'],
    ];

    protected string|array|null $listTenantScope = ['feature.project.workspace', 'tenant_id'];

    protected array $listContextRelations = [
        'feature.project.workspace',
    ];

    public function __construct()
    {
        $this->model = new Scenario();
    }

    public function create(array $data)
    {
        $scenario = new Scenario($this->filterFillable($data));
        $scenario->syncDocumentFields();
        $scenario->save();

        return $scenario->fresh();
    }

    public function update($id, array $newData)
    {
        /** @var Scenario $scenario */
        $scenario = Scenario::query()->findOrFail($id);
        $scenario->fill($this->filterFillable($newData));
        $scenario->syncDocumentFields();
        $scenario->save();

        return $scenario->fresh();
    }

    protected function attachParentContextIds(Model $model): void
    {
        if ($model->relationLoaded('feature') && $model->feature instanceof Feature) {
            /** @var Feature $feature */
            $feature = $model->feature;
            $model->setAttribute('project_id', $feature->project_id);

            if ($feature->relationLoaded('project') && $feature->project) {
                $model->setAttribute('workspace_id', $feature->project->workspace_id);
            }

            return;
        }

        parent::attachParentContextIds($model);
    }
}
