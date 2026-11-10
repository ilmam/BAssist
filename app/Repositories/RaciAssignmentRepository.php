<?php

namespace App\Repositories;

use App\Data\RaciAssignmentData;
use App\Data\RaciAssignmentViewData;
use App\Models\RaciAssignment;
use App\Models\RaciItem;
use App\Models\Stakeholder;
use App\Support\RaciRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class RaciAssignmentRepository extends BaseRepository
{
    public Model $model;
    public $editDto = RaciAssignmentData::class;
    public $viewDto = RaciAssignmentViewData::class;

    protected array $listFilters = [
        'project_id',
        'raci_item_id',
        'stakeholder_id',
        'role',
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
        $this->model = new RaciAssignment();
    }

    public function create(array $data)
    {
        $this->assertValid($data);

        return parent::create($data);
    }

    public function update($id, array $newData)
    {
        $existing = $this->findModel($id);
        $this->assertValid(
            array_merge($existing->only(['project_id', 'raci_item_id', 'stakeholder_id', 'role']), $newData),
            (int) $id
        );

        return parent::update($id, $newData);
    }

    /**
     * BABOK §10.43.3: only one Accountable per item; one role per stakeholder per item;
     * item and stakeholder must belong to the assignment's project.
     *
     * @param  array<string, mixed>  $data
     */
    protected function assertValid(array $data, ?int $ignoreId = null): void
    {
        $role = (string) ($data['role'] ?? '');
        $itemId = (int) ($data['raci_item_id'] ?? 0);
        $stakeholderId = (int) ($data['stakeholder_id'] ?? 0);
        $projectId = (int) ($data['project_id'] ?? 0);
        $errors = [];

        if (! in_array($role, RaciRole::values(), true)) {
            $errors['role'] = 'Choose Responsible, Accountable, Consulted or Informed.';
        }

        if ($itemId > 0 && ! RaciItem::query()->whereKey($itemId)->where('project_id', $projectId)->exists()) {
            $errors['raci_item_id'] = 'The RACI item must belong to the same project.';
        }

        if ($stakeholderId > 0 && ! Stakeholder::query()->whereKey($stakeholderId)->where('project_id', $projectId)->exists()) {
            $errors['stakeholder_id'] = 'The stakeholder must belong to the same project.';
        }

        if ($errors === [] && $itemId > 0 && $stakeholderId > 0) {
            $others = RaciAssignment::query()
                ->where('raci_item_id', $itemId)
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId));

            if ((clone $others)->where('stakeholder_id', $stakeholderId)->exists()) {
                $errors['stakeholder_id'] = 'This stakeholder already has a role on this item. Edit that assignment instead.';
            } elseif ($role === RaciRole::ACCOUNTABLE && (clone $others)->where('role', RaciRole::ACCOUNTABLE)->exists()) {
                $errors['role'] = 'Only one stakeholder can be Accountable for an item.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
