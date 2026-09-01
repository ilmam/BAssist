<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Services\ChangeRequestAffectedService;
use App\Services\ChangeRequestTaintService;
use App\Support\ChangeRequestStatus;
use App\Support\EntityAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChangeRequestController extends CrudController
{
    public function __construct(
        protected ChangeRequestAffectedService $affected,
        protected ChangeRequestTaintService $taint,
    ) {
    }

    /**
     * The "Approved" status option only exists in the select list so that an
     * already-approved CR can still render its current value. Draft/under-review
     * CRs must go through the dedicated Approve & mark for revision flow, so
     * strip "Approved" from the dropdown for them — otherwise picking it here
     * and saving is rejected by ChangeRequestRepository::assertNoDirectApprove()
     * with a confusing "Save failed" error.
     */
    protected function buildEditForm($id): array
    {
        $form = parent::buildEditForm($id);

        $status = (string) ($form['dto']->status ?? ChangeRequestStatus::DRAFT);
        if (! in_array($status, [ChangeRequestStatus::APPROVED, ChangeRequestStatus::IMPLEMENTED], true)) {
            unset($form['formFields']['status']['list'][ChangeRequestStatus::APPROVED]);
        }

        return $form;
    }

    public function show($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        $changeRequest = $this->modelRepository->findModel((int) $id);

        return view(model_page_view($this->modelName, 'details'), [
            'dto' => $dto,
            'model' => $this->modelName,
            'fields' => $fields,
            'cascade' => $this->affected->cascadeFor($changeRequest),
            'canApprove' => $this->canApprove($changeRequest),
            'approveUrl' => route('change_requests.approve-taint', $changeRequest),
        ]);
    }

    public function modalView($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        $changeRequest = $this->modelRepository->findModel((int) $id);
        $data = [
            'dto' => $dto,
            'model' => $this->modelName,
            'fields' => $fields,
            'cascade' => $this->affected->cascadeFor($changeRequest),
            'canApprove' => $this->canApprove($changeRequest),
            'approveUrl' => route('change_requests.approve-taint', $changeRequest),
        ];

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'view'),
            $data,
            model_page_view($this->modelName, 'details'),
            $data
        );
    }

    public function approveTaintForm($id)
    {
        EntityAccess::authorize(auth()->user(), 'ChangeRequest', EntityAccess::UPDATE);

        $changeRequest = $this->modelRepository->findModel((int) $id, ['stakeholderNeed']);
        if (! $this->canApprove($changeRequest)) {
            throw ValidationException::withMessages([
                'status' => __('ui.change_request_cannot_approve'),
            ]);
        }

        $data = [
            'changeRequest' => $changeRequest,
            'candidates' => $this->taint->candidatesFor($changeRequest),
            'submitUrl' => route('change_requests.approve-taint.store', $changeRequest),
        ];

        return $this->respondModalOrPage(
            'pages.change_requests.modals.approve-taint',
            $data,
            'pages.change_requests.approve-taint',
            $data
        );
    }

    public function approveTaintStore(Request $request, $id)
    {
        EntityAccess::authorize(auth()->user(), 'ChangeRequest', EntityAccess::UPDATE);

        $changeRequest = $this->modelRepository->findModel((int) $id);
        $selected = $request->input('taint_items', []);
        if (! is_array($selected)) {
            $selected = [];
        }

        $this->taint->approveAndTaint($changeRequest, $selected);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => __('ui.change_request_approved'),
                'redirect' => model_route('ChangeRequest', 'show', $changeRequest->id),
            ]);
        }

        return redirect()
            ->to(model_route('ChangeRequest', 'show', $changeRequest->id))
            ->with('status', __('ui.change_request_approved'));
    }

    /**
     * Prefill create from sticky project plus optional SN query
     * (e.g. Request change on a Stakeholder Need).
     */
    protected function applyStickyContextDefaults(object $dto): object
    {
        $dto = parent::applyStickyContextDefaults($dto);
        $payload = method_exists($dto, 'toArray') ? $dto->toArray() : [];

        $projectId = (int) request()->query('project_id', 0);
        if ($projectId > 0 && array_key_exists('project_id', $payload)) {
            $payload['project_id'] = $projectId;
        }

        $snId = (int) request()->query('stakeholder_need_id', 0);
        if ($snId > 0) {
            $payload['stakeholder_need_id'] = $snId;
        }

        return $dto::from($payload);
    }

    protected function canApprove(ChangeRequest $changeRequest): bool
    {
        if (! entity_can('ChangeRequest', EntityAccess::UPDATE)) {
            return false;
        }

        if (! $changeRequest->hasStakeholderNeed()) {
            return false;
        }

        $status = (string) $changeRequest->status;

        return in_array($status, [
            ChangeRequestStatus::DRAFT,
            ChangeRequestStatus::UNDER_REVIEW,
        ], true);
    }
}
