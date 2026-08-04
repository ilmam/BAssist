<?php

namespace App\Repositories;

use App\Data\ChangeRequestData;
use App\Data\ChangeRequestViewData;
use App\Models\ChangeRequest;
use App\Services\ChangeRequestAffectedService;
use App\Support\ChangeRequestImpact;
use App\Support\ChangeRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ChangeRequestRepository extends BaseRepository
{
    public Model $model;

    public $editDto = ChangeRequestData::class;

    public $viewDto = ChangeRequestViewData::class;

    protected array $listFilters = [
        'project_id',
        'status',
        'impact_level',
        'priority_id',
        'affected_type',
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
        $this->model = new ChangeRequest();
    }

    public function getById($Id)
    {
        /** @var ChangeRequest $model */
        $model = $this->model::query()->with(['project.workspace', 'priority'])->findOrFail($Id);

        return $this->toViewData($model);
    }

    public function getAll(?array $filters = null)
    {
        $collection = $this->getCollectionWithBelongsTo(true, null, $filters);

        return ChangeRequestViewData::collect(
            $collection->map(fn (ChangeRequest $model) => $this->toViewData($model))->all()
        );
    }

    public function create(array $data)
    {
        $this->assertGates($data);

        return parent::create($data);
    }

    public function update($id, array $newData)
    {
        /** @var ChangeRequest $existing */
        $existing = $this->model::query()->findOrFail($id);
        $merged = array_merge($existing->only([
            'affected_type',
            'affected_id',
            'impact_level',
            'impact_notes',
            'status',
        ]), $newData);

        $this->assertGates($merged);

        return parent::update($id, $newData);
    }

    protected function affected(): ChangeRequestAffectedService
    {
        return app(ChangeRequestAffectedService::class);
    }

    protected function toViewData(ChangeRequest $model): ChangeRequestViewData
    {
        $base = ChangeRequestViewData::from($model)->toArray();
        $base['affected_label'] = $this->affected()->labelFor(
            $model->affected_type,
            $model->affected_id !== null ? (int) $model->affected_id : null,
        );

        return ChangeRequestViewData::from($base);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertGates(array $data): void
    {
        $this->assertHighImpactHasNotes($data);
        $this->assertAffectedWhenRequired($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertHighImpactHasNotes(array $data): void
    {
        $level = (string) ($data['impact_level'] ?? ChangeRequestImpact::MEDIUM);
        if ($level !== ChangeRequestImpact::HIGH) {
            return;
        }

        if (trim((string) ($data['impact_notes'] ?? '')) !== '') {
            return;
        }

        throw ValidationException::withMessages([
            'impact_notes' => __('ui.change_request_high_impact_notes_required'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertAffectedWhenRequired(array $data): void
    {
        $status = (string) ($data['status'] ?? ChangeRequestStatus::DRAFT);

        if (! in_array($status, ChangeRequestStatus::requiresAffected(), true)) {
            return;
        }

        $type = trim((string) ($data['affected_type'] ?? ''));
        $id = isset($data['affected_id']) ? (int) $data['affected_id'] : 0;

        if ($type !== '' && $id > 0) {
            return;
        }

        throw ValidationException::withMessages([
            'affected_type' => __('ui.change_request_affected_required'),
            'affected_id' => __('ui.change_request_affected_required'),
        ]);
    }
}
