<?php

namespace App\Repositories;

use App\Data\BusinessObjectiveData;
use App\Data\BusinessObjectiveViewData;
use App\Helpers\ListUi;
use App\Models\BusinessObjective;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusinessObjectiveRepository extends BaseRepository
{
    public Model $model;
    public $editDto = BusinessObjectiveData::class;
    public $viewDto = BusinessObjectiveViewData::class;

    protected array $listFilters = [
        'project_id',
    ];

    protected array $listRelationFilters = [
        'business_need_id' => 'businessNeeds',
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
        'businessNeeds',
        'stakeholderNeeds',
    ];

    public function __construct()
    {
        $this->model = new BusinessObjective();
    }

    /**
     * Objectives with no parent business need (orphan "what" without a "why").
     */
    protected function applyOrphanConstraint(Builder $query): void
    {
        $query->whereDoesntHave('businessNeeds');
    }

    protected function isOrphan(Model $model): bool
    {
        return (int) ($model->getAttribute('business_needs_count') ?? 0) === 0;
    }

    /**
     * @param  Collection<int, Model>  $collection
     */
    protected function enrichListCollection(Collection $collection): void
    {
        $collection->load('businessNeeds');

        parent::enrichListCollection($collection);

        $collection->each(function (Model $model): void {
            /** @var BusinessObjective $model */
            $primary = $model->businessNeeds->firstWhere('pivot.is_primary', true)
                ?? $model->businessNeeds->first();

            $label = $primary
                ? trim(($primary->code ? $primary->code.' ' : '').$primary->title)
                : null;

            $model->setAttribute(
                'primary_business_need_cell',
                ListUi::relatedEntityCell('BusinessNeed', $primary?->id, $label)
            );
        });
    }

    public function editById($Id)
    {
        /** @var BusinessObjective $objective */
        $objective = $this->model::with('businessNeeds')->findOrFail($Id);
        $dto = BusinessObjectiveData::from($objective);
        $primary = $objective->businessNeeds->firstWhere('pivot.is_primary', true)
            ?? $objective->businessNeeds->first();

        return BusinessObjectiveData::from([
            ...$dto->toArray(),
            'primary_business_need_id' => $primary?->id,
        ]);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $needId = $this->extractNeedId($data);

            /** @var BusinessObjective $objective */
            $objective = $this->model::create($data);

            if ($needId !== null) {
                $this->syncNeeds($objective, [$needId], $needId);
            }

            return $objective;
        });
    }

    public function update($id, array $newData)
    {
        return DB::transaction(function () use ($id, $newData) {
            $needId = $this->extractNeedId($newData);

            /** @var BusinessObjective $objective */
            $objective = $this->model::findOrFail($id);
            $existingIds = $objective->businessNeeds()->pluck('business_needs.id')->all();

            $objective->update($newData);

            if ($needId !== null) {
                $syncIds = array_values(array_unique(array_merge($existingIds, [$needId])));
                $this->syncNeeds($objective, $syncIds, $needId);
            }

            return $objective->refresh();
        });
    }

    protected function extractNeedId(array &$data): ?int
    {
        $raw = $data['primary_business_need_id'] ?? null;
        unset($data['primary_business_need_id']);

        if ($raw === null || $raw === '' || (int) $raw <= 0) {
            return null;
        }

        return (int) $raw;
    }

    /**
     * @param  list<int>  $needIds
     */
    protected function syncNeeds(BusinessObjective $objective, array $needIds, int $primaryId): void
    {
        $payload = [];
        foreach ($needIds as $id) {
            $payload[$id] = ['is_primary' => (int) $id === $primaryId];
        }

        $objective->businessNeeds()->sync($payload);
    }
}
