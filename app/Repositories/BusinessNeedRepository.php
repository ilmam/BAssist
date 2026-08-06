<?php

namespace App\Repositories;

use App\Data\BusinessNeedData;
use App\Data\BusinessNeedViewData;
use App\Models\BusinessNeed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'stakeholder_need_id' => 'stakeholderNeeds',
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
        'stakeholderNeeds',
    ];

    public function __construct()
    {
        $this->model = new BusinessNeed();
    }

    protected function applyOrphanConstraint(Builder $query): void
    {
        $query->whereDoesntHave('businessObjectives');
    }

    protected function isOrphan(Model $model): bool
    {
        return (int) ($model->getAttribute('business_objectives_count') ?? 0) === 0;
    }

    public function editById($Id)
    {
        /** @var BusinessNeed $need */
        $need = $this->model::with('businessObjectives')->findOrFail($Id);
        $dto = BusinessNeedData::from($need);
        $primary = $need->businessObjectives->firstWhere('pivot.is_primary', true)
            ?? $need->businessObjectives->first();

        return BusinessNeedData::from([
            ...$dto->toArray(),
            'primary_business_objective_id' => $primary?->id,
        ]);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $objectiveId = $this->extractObjectiveId($data);

            /** @var BusinessNeed $need */
            $need = $this->model::create($data);

            if ($objectiveId !== null) {
                $this->syncObjectives($need, [$objectiveId], $objectiveId);
            }

            return $need;
        });
    }

    public function update($id, array $newData)
    {
        return DB::transaction(function () use ($id, $newData) {
            $objectiveId = $this->extractObjectiveId($newData);

            /** @var BusinessNeed $need */
            $need = $this->model::findOrFail($id);
            $existingIds = $need->businessObjectives()->pluck('business_objectives.id')->all();

            $need->update($newData);

            if ($objectiveId !== null) {
                $syncIds = array_values(array_unique(array_merge($existingIds, [$objectiveId])));
                $this->syncObjectives($need, $syncIds, $objectiveId);
            }

            return $need->refresh();
        });
    }

    protected function extractObjectiveId(array &$data): ?int
    {
        $raw = $data['primary_business_objective_id'] ?? null;
        unset($data['primary_business_objective_id']);

        if ($raw === null || $raw === '' || (int) $raw <= 0) {
            return null;
        }

        return (int) $raw;
    }

    /**
     * @param  list<int>  $objectiveIds
     */
    protected function syncObjectives(BusinessNeed $need, array $objectiveIds, int $primaryId): void
    {
        $payload = [];
        foreach ($objectiveIds as $id) {
            $payload[$id] = ['is_primary' => (int) $id === $primaryId];
        }

        $need->businessObjectives()->sync($payload);
    }
}
