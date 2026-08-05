<?php

namespace App\Http\Controllers;

use App\Models\StakeholderNeed;
use App\Support\EntityAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SwimlaneFlowController extends CrudController
{
    public function stakeholderNeedOptions(Request $request): JsonResponse
    {
        EntityAccess::authorize(auth()->user(), 'SwimlaneFlow', EntityAccess::VIEW);

        $projectId = $request->query('project_id');
        $projectId = is_numeric($projectId) ? (int) $projectId : null;

        return response()->json([
            'options' => $this->stakeholderNeedOptionsForProject($projectId),
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

        return view(model_page_view($this->modelName, 'details'), $this->viewData($dto, $fields));
    }

    public function modalView($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        $data = $this->viewData($dto, $fields);

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'view'),
            $data,
            model_page_view($this->modelName, 'details'),
            $data
        );
    }

    /**
     * @param  array{dto: object, formFields: array<string, array<string, mixed>>}  $form
     * @return array{dto: object, model: string, formFields: array<string, array<string, mixed>>, operation: string, stakeholderNeedOptions: list<array{value: string, label: string}>, stakeholderNeedOptionsUrl: string}
     */
    protected function formViewData(array $form, string $operation): array
    {
        $projectId = isset($form['dto']->project_id) && (int) $form['dto']->project_id > 0
            ? (int) $form['dto']->project_id
            : null;

        return [
            'dto' => $form['dto'],
            'model' => $this->modelName,
            'formFields' => $form['formFields'],
            'operation' => $operation,
            'stakeholderNeedOptions' => $this->stakeholderNeedOptionsForProject($projectId),
            'stakeholderNeedOptionsUrl' => route('swimlane_flows.stakeholder-need-options'),
        ];
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array{dto: object, model: string, fields: array<string, mixed>, stakeholderNeedOptions: list<array{value: string, label: string}>}
     */
    protected function viewData(object $dto, array $fields): array
    {
        $projectId = isset($dto->project_id) && (int) $dto->project_id > 0
            ? (int) $dto->project_id
            : null;

        return [
            'dto' => $dto,
            'model' => $this->modelName,
            'fields' => $fields,
            'stakeholderNeedOptions' => $this->stakeholderNeedOptionsForProject($projectId),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    protected function stakeholderNeedOptionsForProject(?int $projectId): array
    {
        if ($projectId === null || $projectId < 1) {
            return [];
        }

        return StakeholderNeed::query()
            ->where('project_id', $projectId)
            ->orderBy('number')
            ->orderBy('title')
            ->get()
            ->map(function (StakeholderNeed $need) {
                $code = $need->code ? $need->code.' — ' : '';

                return [
                    'value' => (string) $need->id,
                    'label' => $code.$need->title,
                ];
            })
            ->all();
    }
}
