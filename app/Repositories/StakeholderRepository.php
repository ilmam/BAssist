<?php

namespace App\Repositories;

use App\Data\StakeholderData;
use App\Data\StakeholderViewData;
use App\Models\Stakeholder;
use App\Support\ProjectContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class StakeholderRepository extends BaseRepository
{
    public Model $model;
    public $editDto = StakeholderData::class;
    public $viewDto = StakeholderViewData::class;

    protected array $listFilters = [
        'project_id',
        'status_id',
    ];

    protected array $listRelationFilters = [
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
        'stakeholderNeeds',
    ];

    public function __construct()
    {
        $this->model = new Stakeholder();
    }

    /**
     * Custom stakeholders first, then system defaults; name within each group.
     */
    protected function newListQuery(?array $filters = null): Builder
    {
        return $this->orderCustomBeforeSystem(parent::newListQuery($filters));
    }

    public function getSelectOptions($fields = null)
    {
        if ($fields == null) {
            $fields = $this->model->getListFields();
        }

        if ($this->selectOptionsRequireStickyProject() && app(ProjectContext::class)->id() === null) {
            return collect();
        }

        $query = $this->model::query();
        $this->applyStickyProjectScope($query);

        return $this->orderCustomBeforeSystem($query)->pluck(...$fields);
    }

    protected function orderCustomBeforeSystem(Builder $query): Builder
    {
        return $query
            ->orderBy('is_system')
            ->orderBy('name');
    }

    public function create(array $data)
    {
        $data['is_system'] = false;
        $data['system_key'] = null;

        return $this->model::create($data);
    }

    public function update($id, array $newData)
    {
        /** @var Stakeholder $stakeholder */
        $stakeholder = $this->model::findOrFail($id);

        if ($stakeholder->is_system) {
            // System stakeholders keep their locked identity; allow notes/influence edits only.
            $newData = array_intersect_key($newData, array_flip([
                'influence',
                'interest',
                'notes',
                'status_id',
            ]));
        } else {
            unset($newData['is_system'], $newData['system_key']);
        }

        $stakeholder->update($newData);

        return $stakeholder;
    }

    public function delete($Id)
    {
        /** @var Stakeholder $stakeholder */
        $stakeholder = $this->model::findOrFail($Id);

        if ($stakeholder->is_system) {
            throw new RuntimeException('System stakeholders cannot be deleted.');
        }

        $stakeholder->delete();
    }
}
