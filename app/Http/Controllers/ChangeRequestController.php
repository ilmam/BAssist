<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Services\ChangeRequestAffectedService;
use App\Support\ChangeRequestAffectedType;
use App\Support\EntityAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChangeRequestController extends CrudController
{
    public function __construct(protected ChangeRequestAffectedService $affected)
    {
    }

    public function affectedOptions(Request $request): JsonResponse
    {
        EntityAccess::authorize(auth()->user(), 'ChangeRequest', EntityAccess::VIEW);

        $type = (string) $request->query('type', '');
        $projectId = $request->query('project_id');
        $projectId = is_numeric($projectId) ? (int) $projectId : null;

        if ($type !== '' && ! in_array($type, ChangeRequestAffectedType::values(), true)) {
            return response()->json(['options' => []]);
        }

        $options = $this->affected->optionsFor($type !== '' ? $type : null, $projectId);

        return response()->json([
            'options' => collect($options)
                ->map(fn (string $label, int|string $id) => ['id' => (int) $id, 'label' => $label])
                ->values()
                ->all(),
        ]);
    }

    public function create()
    {
        $form = $this->buildCreateForm();

        return view(model_page_view($this->modelName, 'form'), $this->formViewData($form, 'create'));
    }

    public function edit($id)
    {
        $form = $this->buildEditForm($id);

        return view(model_page_view($this->modelName, 'form'), $this->formViewData($form, 'edit'));
    }

    public function modalCreate()
    {
        $form = $this->buildCreateForm();
        $data = $this->formViewData($form, 'create');

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'form'),
            $data,
            model_page_view($this->modelName, 'form'),
            $data
        );
    }

    public function modalEdit($id)
    {
        $form = $this->buildEditForm($id);
        $data = $this->formViewData($form, 'edit');

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'form'),
            $data,
            model_page_view($this->modelName, 'form'),
            $data
        );
    }

    public function show($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);

        return view(model_page_view($this->modelName, 'details'), [
            'dto' => $dto,
            'model' => $this->modelName,
            'fields' => $fields,
            'cascade' => $this->cascadeForId((int) $id),
        ]);
    }

    public function modalView($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        $data = [
            'dto' => $dto,
            'model' => $this->modelName,
            'fields' => $fields,
            'cascade' => $this->cascadeForId((int) $id),
        ];

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'view'),
            $data,
            model_page_view($this->modelName, 'details'),
            $data
        );
    }

    protected function buildCreateForm(bool $forQuickCreate = false): array
    {
        return $this->withAffectedOptions(parent::buildCreateForm($forQuickCreate));
    }

    protected function buildEditForm($id): array
    {
        return $this->withAffectedOptions(parent::buildEditForm($id));
    }

    /**
     * Prefill create from sticky project plus optional subject query params
     * (e.g. Request change on a Business Objective).
     */
    protected function applyStickyContextDefaults(object $dto): object
    {
        $dto = parent::applyStickyContextDefaults($dto);
        $payload = method_exists($dto, 'toArray') ? $dto->toArray() : [];

        $projectId = (int) request()->query('project_id', 0);
        if ($projectId > 0 && array_key_exists('project_id', $payload)) {
            $payload['project_id'] = $projectId;
        }

        $type = trim((string) request()->query('affected_type', ''));
        if ($type !== '' && in_array($type, ChangeRequestAffectedType::values(), true)) {
            $payload['affected_type'] = $type;
        }

        $affectedId = (int) request()->query('affected_id', 0);
        if ($affectedId > 0) {
            $payload['affected_id'] = $affectedId;
        }

        return $dto::from($payload);
    }

    /**
     * @param  array{dto: object, formFields: array<string, array<string, mixed>>, hiddenDefaults?: array<string, mixed>, affectedOptionsUrl?: string}  $form
     * @return array{dto: object, model: string, formFields: array<string, array<string, mixed>>, operation: string, affectedOptionsUrl: string}
     */
    protected function formViewData(array $form, string $operation): array
    {
        return [
            'dto' => $form['dto'],
            'model' => $this->modelName,
            'formFields' => $form['formFields'],
            'operation' => $operation,
            'affectedOptionsUrl' => $form['affectedOptionsUrl'] ?? route('change_requests.affected-options'),
        ];
    }

    /**
     * @param  array{dto: object, formFields: array<string, array<string, mixed>>, hiddenDefaults?: array<string, mixed>}  $form
     * @return array{dto: object, formFields: array<string, array<string, mixed>>, hiddenDefaults?: array<string, mixed>, affectedOptionsUrl: string}
     */
    protected function withAffectedOptions(array $form): array
    {
        $dto = $form['dto'];
        $type = is_string($dto->affected_type ?? null) ? $dto->affected_type : null;
        $projectId = isset($dto->project_id) && (int) $dto->project_id > 0
            ? (int) $dto->project_id
            : null;

        if (isset($form['formFields']['affected_id'])) {
            $form['formFields']['affected_id']['list'] = $this->affected->optionsFor($type, $projectId);
        }

        $form['affectedOptionsUrl'] = route('change_requests.affected-options');

        return $form;
    }

    /**
     * @return list<array{type: string, code: string|null, title: string}>
     */
    protected function cascadeForId(int $id): array
    {
        $changeRequest = ChangeRequest::query()->find($id);
        if ($changeRequest === null) {
            return [];
        }

        return $this->affected->cascadeFor($changeRequest);
    }
}
