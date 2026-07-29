<?php

namespace App\Repositories;

use App\Data\StrategicBaselineData;
use App\Data\StrategicBaselineViewData;
use App\Models\Project;
use App\Models\StrategicBaseline;
use App\Support\StrategicBaselineStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class StrategicBaselineRepository extends BaseRepository
{
    public Model $model;

    public $editDto = StrategicBaselineData::class;

    public $viewDto = StrategicBaselineViewData::class;

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
        $this->model = new StrategicBaseline();
    }

    public function findOrCreateForProject(Project $project): StrategicBaseline
    {
        $existing = StrategicBaseline::query()
            ->where('project_id', $project->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return StrategicBaseline::query()->create([
            'project_id' => $project->id,
            'current_state' => null,
            'future_state' => null,
            'change_strategy' => null,
            'status' => StrategicBaselineStatus::default(),
        ]);
    }

    public function create(array $data)
    {
        $this->assertUniqueProject($data['project_id'] ?? null);

        return $this->model::create($this->filterFillable($data));
    }

    public function update($id, array $newData)
    {
        /** @var StrategicBaseline $baseline */
        $baseline = $this->model::findOrFail($id);

        if (isset($newData['project_id']) && (int) $newData['project_id'] !== (int) $baseline->project_id) {
            $this->assertUniqueProject((int) $newData['project_id'], (int) $baseline->id);
        }

        $baseline->update($this->filterFillable($newData));

        return $baseline->refresh();
    }

    protected function assertUniqueProject(?int $projectId, ?int $ignoreId = null): void
    {
        if ($projectId === null || $projectId <= 0) {
            return;
        }

        $query = StrategicBaseline::query()->where('project_id', $projectId);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'project_id' => __('ui.strategic_baseline_one_per_project'),
            ]);
        }
    }
}
