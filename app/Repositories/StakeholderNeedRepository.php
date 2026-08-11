<?php

namespace App\Repositories;

use App\Data\StakeholderNeedData;
use App\Data\StakeholderNeedViewData;
use App\Helpers\ListUi;
use App\Models\StakeholderNeed;
use App\Support\ProjectContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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
        'business_objective_id' => 'businessObjectives',
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
        'businessObjectives',
        'stakeholders',
    ];

    public function __construct()
    {
        $this->model = new StakeholderNeed();
    }

    public function getSelectOptions($fields = null)
    {
        if ($this->selectOptionsRequireStickyProject() && app(ProjectContext::class)->id() === null) {
            return [];
        }

        $query = $this->model::query();
        $this->applyStickyProjectScope($query);

        return $query
            ->orderBy('number')
            ->orderBy('title')
            ->get(['id', 'number', 'title'])
            ->mapWithKeys(function (StakeholderNeed $need) {
                $label = trim(($need->code ? $need->code.' — ' : '').$need->title);

                return [$need->id => $label !== '' ? $label : (string) $need->id];
            })
            ->all();
    }

    protected function applyOrphanConstraint(Builder $query): void
    {
        $query->where(function (Builder $inner) {
            $inner->whereDoesntHave('businessObjectives')
                ->orWhereDoesntHave('stakeholders');
        });
    }

    protected function isOrphan(Model $model): bool
    {
        return (int) ($model->getAttribute('business_objectives_count') ?? 0) === 0
            || (int) ($model->getAttribute('stakeholders_count') ?? 0) === 0;
    }

    /**
     * @param  Collection<int, Model>  $collection
     */
    protected function enrichListCollection(Collection $collection): void
    {
        $collection->load('businessObjectives');

        parent::enrichListCollection($collection);

        $collection->each(function (Model $model): void {
            /** @var StakeholderNeed $model */
            $primary = $model->businessObjectives->first();
            $label = $primary
                ? trim(($primary->code ? $primary->code.' ' : '').$primary->title)
                : null;

            $model->setAttribute(
                'primary_business_objective_cell',
                ListUi::relatedEntityCell('BusinessObjective', $primary?->id, $label)
            );
        });
    }

    public function editById($Id)
    {
        /** @var StakeholderNeed $need */
        $need = $this->model::with(['businessObjectives', 'stakeholders'])->findOrFail($Id);
        $dto = StakeholderNeedData::from($need);

        return StakeholderNeedData::from([
            ...$dto->toArray(),
            'business_objective_id' => $need->businessObjectives->first()?->id ?? 0,
            'stakeholder_id' => $need->stakeholders->first()?->id ?? 0,
        ]);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            [$businessObjectiveId, $stakeholderId] = $this->extractLinks($data);

            /** @var StakeholderNeed $need */
            $need = $this->model::create($data);
            $need->businessObjectives()->sync([$businessObjectiveId]);
            $need->stakeholders()->sync([$stakeholderId]);

            return $need;
        });
    }

    public function update($id, array $newData)
    {
        return DB::transaction(function () use ($id, $newData) {
            [$businessObjectiveId, $stakeholderId] = $this->extractLinks($newData);

            /** @var StakeholderNeed $need */
            $need = $this->model::findOrFail($id);
            $need->update($newData);
            $need->businessObjectives()->sync([$businessObjectiveId]);
            $need->stakeholders()->sync([$stakeholderId]);

            return $need;
        });
    }

    /**
     * @return array{0:int,1:int}
     */
    protected function extractLinks(array &$data): array
    {
        $businessObjectiveId = (int) ($data['business_objective_id'] ?? 0);
        $stakeholderId = (int) ($data['stakeholder_id'] ?? 0);
        unset($data['business_objective_id'], $data['stakeholder_id']);

        if ($businessObjectiveId <= 0 || $stakeholderId <= 0) {
            throw ValidationException::withMessages([
                'business_objective_id' => 'A business objective is required.',
                'stakeholder_id' => 'A stakeholder is required.',
            ]);
        }

        return [$businessObjectiveId, $stakeholderId];
    }
}
