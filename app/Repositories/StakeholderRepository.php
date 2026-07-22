<?php

namespace App\Repositories;

use App\Data\StakeholderData;
use App\Data\StakeholderViewData;
use App\Models\Stakeholder;
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
