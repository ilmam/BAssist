<?php

namespace App\Repositories;

use App\Data\ScenarioData;
use App\Data\ScenarioViewData;
use App\Models\Feature;
use App\Models\Scenario;
use App\Models\StakeholderNeed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ScenarioRepository extends BaseRepository
{
    public Model $model;
    public $editDto = ScenarioData::class;
    public $viewDto = ScenarioViewData::class;

    protected array $listFilters = [
        'feature_id',
        'stakeholder_need_id',
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
        $this->assertCoveringNeedInProject($data);
        $scenario = new Scenario($this->filterFillable($data));
        $scenario->syncDocumentFields();
        $scenario->save();

        return $scenario->fresh();
    }

    public function update($id, array $newData)
    {
        /** @var Scenario $scenario */
        $scenario = Scenario::query()->findOrFail($id);
        $this->assertCoveringNeedInProject(array_merge(
            ['feature_id' => $scenario->feature_id],
            $newData,
        ));
        $scenario->fill($this->filterFillable($newData));
        $scenario->syncDocumentFields();
        $scenario->save();

        return $scenario->fresh();
    }

    public function findForDocument(int $id): Scenario
    {
        /** @var Scenario $scenario */
        $scenario = $this->findModel($id, ['feature.project', 'stakeholderNeed', 'status']);

        return $scenario;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertCoveringNeedInProject(array $data): void
    {
        $needId = (int) ($data['stakeholder_need_id'] ?? 0);
        if ($needId <= 0) {
            return;
        }

        $featureId = (int) ($data['feature_id'] ?? 0);
        $feature = Feature::query()->find($featureId);
        $need = StakeholderNeed::query()->find($needId);

        if ($feature === null || $need === null || (int) $need->project_id !== (int) $feature->project_id) {
            throw ValidationException::withMessages([
                'stakeholder_need_id' => __('ui.scenario_covering_need_project_mismatch'),
            ]);
        }
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
