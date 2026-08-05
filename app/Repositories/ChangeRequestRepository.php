<?php

namespace App\Repositories;

use App\Data\ChangeRequestData;
use App\Data\ChangeRequestViewData;
use App\Models\ChangeRequest;
use App\Support\ChangeRequestImpact;
use App\Support\ChangeRequestStatus;
use App\Support\ProjectContext;
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
        'stakeholder_need_id',
    ];

    protected array $listContextFilters = [
        'workspace_id' => ['project', 'workspace_id'],
    ];

    protected string|array|null $listTenantScope = ['project.workspace', 'tenant_id'];

    protected array $listContextRelations = [
        'project.workspace',
        'stakeholderNeed',
    ];

    public function __construct()
    {
        $this->model = new ChangeRequest();
    }

    /**
     * Options for FR/Feature parent select — approved CRs only, scoped to the
     * sticky project context (mirrors BaseController::applyStickyContextDefaults())
     * so cross-project CRs never leak into another project's dropdown. When no
     * project is in scope (e.g. no sticky context yet), falls back to unscoped
     * to avoid hiding valid options.
     *
     * Leading blank keeps change_request_id optional on FR/Feature selects
     * (mirrors SwimlaneFlowStepRepository::getSelectOptions()) — without it the
     * browser auto-selects the first CR and the field is effectively required.
     *
     * @return array<int|string, string>
     */
    public function getSelectOptions($fields = null)
    {
        $projectId = app(ProjectContext::class)->id();

        $options = $this->model::query()
            ->whereIn('status', [ChangeRequestStatus::APPROVED, ChangeRequestStatus::IMPLEMENTED])
            ->when($projectId !== null, fn ($query) => $query->where('project_id', $projectId))
            ->orderBy('number')
            ->get()
            ->mapWithKeys(function (ChangeRequest $item) {
                $label = trim(($item->code ? $item->code.' — ' : '').$item->title);

                return [$item->getKey() => $label !== '' ? $label : (string) $item->getKey()];
            });

        return collect(['' => ''])->union($options)->all();
    }

    public function getById($Id)
    {
        /** @var ChangeRequest $model */
        $model = $this->model::query()
            ->with(['project.workspace', 'priority', 'stakeholderNeed'])
            ->findOrFail($Id);

        return ChangeRequestViewData::from($model);
    }

    public function create(array $data)
    {
        $this->assertGates($data, null);

        return parent::create($data);
    }

    public function update($id, array $newData)
    {
        /** @var ChangeRequest $existing */
        $existing = $this->model::query()->findOrFail($id);
        $merged = array_merge($existing->only([
            'stakeholder_need_id',
            'impact_level',
            'impact_notes',
            'status',
        ]), $newData);

        $this->assertGates($merged, $existing);

        return parent::update($id, $newData);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertGates(array $data, ?ChangeRequest $existing): void
    {
        $this->assertHighImpactHasNotes($data);
        $this->assertStakeholderNeedWhenRequired($data);
        $this->assertNoDirectApprove($data, $existing);
        $this->assertImplementedRequiresApproved($data, $existing);
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
    protected function assertStakeholderNeedWhenRequired(array $data): void
    {
        $status = (string) ($data['status'] ?? ChangeRequestStatus::DRAFT);

        if (! in_array($status, ChangeRequestStatus::requiresStakeholderNeed(), true)) {
            return;
        }

        if ((int) ($data['stakeholder_need_id'] ?? 0) > 0) {
            return;
        }

        throw ValidationException::withMessages([
            'stakeholder_need_id' => __('ui.change_request_affected_required'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertNoDirectApprove(array $data, ?ChangeRequest $existing): void
    {
        $next = (string) ($data['status'] ?? ChangeRequestStatus::DRAFT);
        if ($next !== ChangeRequestStatus::APPROVED) {
            return;
        }

        $previous = (string) ($existing?->status ?? ChangeRequestStatus::DRAFT);
        if ($previous === ChangeRequestStatus::APPROVED || $previous === ChangeRequestStatus::IMPLEMENTED) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => __('ui.change_request_approve_via_taint'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertImplementedRequiresApproved(array $data, ?ChangeRequest $existing): void
    {
        $next = (string) ($data['status'] ?? ChangeRequestStatus::DRAFT);
        if ($next !== ChangeRequestStatus::IMPLEMENTED) {
            return;
        }

        $previous = (string) ($existing?->status ?? ChangeRequestStatus::DRAFT);
        if (in_array($previous, [ChangeRequestStatus::APPROVED, ChangeRequestStatus::IMPLEMENTED], true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => __('ui.change_request_implement_requires_approved'),
        ]);
    }
}
