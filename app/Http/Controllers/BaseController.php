<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesListFilters;
use App\Http\Controllers\Concerns\RespondsWithModal;
use Illuminate\Http\Request;
use App\Support\DtoMetadata;
use App\Support\EntityFormBuilder;
use App\Support\RepositoryResolver;

class BaseController extends Controller
{
    use ResolvesListFilters;
    use RespondsWithModal;
    public $modelName = '';
    public $modelRepository;

    public function index(Request $request)
    {
        $result = $this->modelRepository->getFirst();
        $columns = DtoMetadata::for($this->modelRepository->viewDto)->listColumns(withPrefix: true);
        $allowedFilters = $this->allowedListFilters();

        return view(model_page_view($this->modelName, 'list'), [
            'dto' => $result,
            'model' => $this->modelName,
            'columns' => $columns,
            'listFilters' => $this->resolveListFilters($request),
            'allowedListFilters' => $allowedFilters,
        ]);
    }

    public function create()
    {
        $form = $this->buildCreateForm();

        return view(model_page_view($this->modelName, 'form'), [
            'dto' => $form['dto'],
            'model' => $this->modelName,
            'formFields' => $form['formFields'],
            'operation' => 'create',
        ]);
    }

    public function modalCreate()
    {
        $form = $this->buildCreateForm();
        $data = [
            'dto' => $form['dto'],
            'model' => $this->modelName,
            'formFields' => $form['formFields'],
            'operation' => 'create',
        ];

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'form'),
            $data,
            model_page_view($this->modelName, 'form'),
            $data
        );
    }

    public function modalQuickCreate()
    {
        $form = $this->buildCreateForm(forQuickCreate: true);
        $data = [
            'dto' => $form['dto'],
            'model' => $this->modelName,
            'formFields' => $form['formFields'],
            'hiddenDefaults' => $form['hiddenDefaults'],
            'operation' => 'create',
            'labelField' => $this->quickCreateLabelField($form['formFields']),
            'sessionColumns' => $this->quickCreateSessionColumns(),
        ];

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'quick-create'),
            $data,
            model_page_view($this->modelName, 'form'),
            [
                'dto' => $form['dto'],
                'model' => $this->modelName,
                'formFields' => $form['formFields'],
                'operation' => 'create',
            ]
        );
    }

    public function store(Request $request)
    {
        $data = $this->getData($request);
        $created = $this->modelRepository->create($data->toArray());

        return $this->respondAfterMutation($request, $created);
    }

    public function show($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);

        return view(model_page_view($this->modelName, 'details'), ['dto' => $dto, 'model' => $this->modelName, 'fields' => $fields]);
    }

    public function modalView($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        $data = ['dto' => $dto, 'model' => $this->modelName, 'fields' => $fields];

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'view'),
            $data,
            model_page_view($this->modelName, 'details'),
            $data
        );
    }

    public function modalShow($id)
    {
        return $this->modalDelete($id);
    }

    public function modalDelete($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        $data = ['dto' => $dto, 'model' => $this->modelName, 'fields' => $fields];

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'delete'),
            $data,
            model_page_view($this->modelName, 'details'),
            $data
        );
    }

    public function modalEdit($id)
    {
        $form = $this->buildEditForm($id);
        $data = [
            'dto' => $form['dto'],
            'model' => $this->modelName,
            'formFields' => $form['formFields'],
            'operation' => 'edit',
        ];

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'form'),
            $data,
            model_page_view($this->modelName, 'form'),
            $data
        );
    }

    public function edit($id)
    {
        $form = $this->buildEditForm($id);

        return view(model_page_view($this->modelName, 'form'), [
            'dto' => $form['dto'],
            'model' => $this->modelName,
            'formFields' => $form['formFields'],
            'operation' => 'edit',
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->getData($request);
        $updated = $this->modelRepository->update($id, $data->toArray());

        // BaseRepository::update returns affected row count; some repos return the model.
        if (! is_object($updated)) {
            $updated = $this->modelRepository->editById($id);
        }

        return $this->respondAfterMutation($request, $updated);
    }

    public function destroy($id)
    {
        $this->modelRepository->delete($id);

        return $this->respondAfterMutation(request());
    }

    protected function respondAfterMutation(Request $request, mixed $record = null)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $payload = ['success' => true];

            if ($record !== null) {
                $payload['record'] = $this->mutationRecordPayload($record);
            }

            return response()->json($payload);
        }

        return redirect()->route(model_route_name($this->modelName, 'index'));
    }

    /**
     * @return array{id: mixed, label: string, values: array<string, mixed>}
     */
    protected function mutationRecordPayload(mixed $record): array
    {
        if (is_object($record) && method_exists($record, 'toArray')) {
            $values = $record->toArray();
        } elseif (is_array($record)) {
            $values = $record;
        } else {
            $values = ['id' => $record];
        }

        $label = $values['title']
            ?? $values['name']
            ?? $values['code']
            ?? ('#'.($values['id'] ?? ''));

        return [
            'id' => $values['id'] ?? null,
            'label' => (string) $label,
            'values' => $values,
        ];
    }

    /**
     * @param  array<string, mixed>  $formFields
     */
    protected function quickCreateLabelField(array $formFields): string
    {
        foreach (['title', 'name', 'code'] as $candidate) {
            if (array_key_exists($candidate, $formFields)) {
                return $candidate;
            }
        }

        $keys = array_keys($formFields);

        return $keys[0] ?? 'id';
    }

    /**
     * Column keys for the Quick Create session table — same visible fields as
     * the Quick Create form (edit DTO, hideQuick = false), in form order,
     * with `id` first when not already included.
     *
     * @return list<string>
     */
    protected function quickCreateSessionColumns(): array
    {
        $columns = array_keys(
            DtoMetadata::for($this->modelRepository->editDto)->quickCreateVisibleFormFields()
        );

        if (! in_array('id', $columns, true)) {
            array_unshift($columns, 'id');
        }

        if ($columns === []) {
            $columns = ['id'];
        }

        return array_values(array_unique($columns));
    }

    private function getData(Request $request)
    {
        $dtoClass = "\\App\\Data\\".$this->modelName.'Data';

        return $dtoClass::from($request);
    }

    protected function buildCreateForm(bool $forQuickCreate = false): array
    {
        $dtoClass = $this->modelRepository->editDto;
        $dto = $dtoClass::from($dtoClass::empty());
        $builder = $this->formBuilder();

        return [
            'dto' => $dto,
            'formFields' => $builder->fields($dtoClass, forQuickCreate: $forQuickCreate),
            'hiddenDefaults' => $forQuickCreate
                ? $builder->quickCreateHiddenDefaults($dtoClass, $dto)
                : [],
        ];
    }

    protected function buildEditForm($id): array
    {
        return [
            'dto' => $this->modelRepository->editById($id),
            'formFields' => $this->formBuilder()->fields($this->modelRepository->editDto),
        ];
    }

    protected function formBuilder(): EntityFormBuilder
    {
        return app(EntityFormBuilder::class);
    }

    protected function initiateModelRepository($modelName)
    {
        return RepositoryResolver::make($modelName);
    }
}
