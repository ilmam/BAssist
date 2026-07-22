<?php

namespace App\Repositories;

use App\Data\StakeholderNeedData;
use App\Data\StakeholderNeedViewData;
use App\Models\StakeholderNeed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StakeholderNeedRepository extends BaseRepository
{
    public Model $model;
    public $editDto = StakeholderNeedData::class;
    public $viewDto = StakeholderNeedViewData::class;

    protected array $listFilters = [
        'project_id',
        'status_id',
        'priority_id',
    ];

    protected array $listRelationFilters = [
        'business_need_id' => 'businessNeeds',
        'stakeholder_id' => 'stakeholders',
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
        'stakeholders',
    ];

    public function __construct()
    {
        $this->model = new StakeholderNeed();
    }

    protected function applyOrphanConstraint(Builder $query): void
    {
        $query->where(function (Builder $inner) {
            $inner->whereDoesntHave('businessNeeds')
                ->orWhereDoesntHave('stakeholders');
        });
    }

    protected function isOrphan(Model $model): bool
    {
        return (int) ($model->getAttribute('business_needs_count') ?? 0) === 0
            || (int) ($model->getAttribute('stakeholders_count') ?? 0) === 0;
    }

    public function editById($Id)
    {
        /** @var StakeholderNeed $need */
        $need = $this->model::with(['businessNeeds', 'stakeholders'])->findOrFail($Id);
        $dto = StakeholderNeedData::from($need);

        return StakeholderNeedData::from([
            ...$dto->toArray(),
            'business_need_id' => $need->businessNeeds->first()?->id ?? 0,
            'stakeholder_id' => $need->stakeholders->first()?->id ?? 0,
        ]);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            [$businessNeedId, $stakeholderId] = $this->extractLinks($data);

            /** @var StakeholderNeed $need */
            $need = $this->model::create($data);
            $need->businessNeeds()->sync([$businessNeedId]);
            $need->stakeholders()->sync([$stakeholderId]);

            return $need;
        });
    }

    public function update($id, array $newData)
    {
        return DB::transaction(function () use ($id, $newData) {
            [$businessNeedId, $stakeholderId] = $this->extractLinks($newData);

            /** @var StakeholderNeed $need */
            $need = $this->model::findOrFail($id);
            $need->update($newData);
            $need->businessNeeds()->sync([$businessNeedId]);
            $need->stakeholders()->sync([$stakeholderId]);

            return $need;
        });
    }

    /**
     * @return array{0:int,1:int}
     */
    protected function extractLinks(array &$data): array
    {
        $businessNeedId = (int) ($data['business_need_id'] ?? 0);
        $stakeholderId = (int) ($data['stakeholder_id'] ?? 0);
        unset($data['business_need_id'], $data['stakeholder_id']);

        if ($businessNeedId <= 0 || $stakeholderId <= 0) {
            throw ValidationException::withMessages([
                'business_need_id' => 'A business need is required.',
                'stakeholder_id' => 'A stakeholder is required.',
            ]);
        }

        return [$businessNeedId, $stakeholderId];
    }
}
