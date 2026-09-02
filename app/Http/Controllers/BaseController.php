<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesListFilters;
use App\Http\Controllers\Concerns\RespondsWithModal;
use App\Services\AttachmentService;
use App\Services\SpineCascadeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Concerns\HasEntityNumber;
use App\Support\AttachableSupport;
use App\Support\DtoMetadata;
use App\Support\EntityFormBuilder;
use App\Support\ProjectContext;
use App\Support\RepositoryResolver;
use App\Support\WorkspaceContext;

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
        $this->mergeStickyContextIntoRequest($request);
        $data = $this->getData($request);
        $created = $this->modelRepository->create($data->toArray());
        $this->syncAttachments($request, $created);

        return $this->respondAfterMutation($request, $created);
    }

    public function show($id)
    {
        return view(model_page_view($this->modelName, 'details'), $this->detailsViewData($id));
    }

    public function modalView($id)
    {
        $data = $this->detailsViewData($id);

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'view'),
            $data,
            model_page_view($this->modelName, 'details'),
            $data
        );
    }

    public function storeAttachment(Request $request, $id)
    {
        $this->assertAttachable();
        $validated = $request->validate([
            'file' => ['required', 'file'],
        ]);

        app(AttachmentService::class)->store($this->modelName, (int) $id, $validated['file']);

        return redirect()
            ->route(model_route_name($this->modelName, 'show'), $id)
            ->with('status', __('ui.attachments_saved'));
    }

    public function showAttachment($id, $attachment)
    {
        $this->assertAttachable();

        return app(AttachmentService::class)->download($this->modelName, (int) $id, (int) $attachment);
    }

    public function destroyAttachment($id, $attachment)
    {
        $this->assertAttachable();
        app(AttachmentService::class)->destroy($this->modelName, (int) $id, (int) $attachment);

        return redirect()
            ->route(model_route_name($this->modelName, 'show'), $id)
            ->with('status', __('ui.attachments_deleted'));
    }

    protected function assertAttachable(): void
    {
        if (! AttachableSupport::enabled($this->modelName)) {
            abort(404);
        }
    }

    /**
     * Shared payload for details pages and view modals.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function detailsViewData($id, array $extra = []): array
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);

        return array_merge([
            'dto' => $dto,
            'model' => $this->modelName,
            'fields' => $fields,
            'cascade' => app(SpineCascadeService::class)->for($this->modelName, (int) $id),
        ], $extra);
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
        $this->mergeStickyContextIntoRequest($request);
        $data = $this->getData($request);
        $updated = $this->modelRepository->update($id, $data->toArray());

        // BaseRepository::update returns affected row count; some repos return the model.
        if (! is_object($updated)) {
            $updated = $this->modelRepository->editById($id);
        }

        $this->syncAttachments($request, $updated, (int) $id);

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

        $id = $this->mutationRecordId($record);

        // Full-page create/update: stay on the edit form for continued work.
        // Modal saves use AJAX above and keep their close/reload flow.
        // Destroy (no record) falls through to the list.
        if ($id !== null) {
            return redirect()
                ->route(model_route_name($this->modelName, 'edit'), $id)
                ->with('status', __('ui.record_saved'));
        }

        return redirect()->route(model_route_name($this->modelName, 'index'));
    }

    protected function mutationRecordId(mixed $record): ?int
    {
        if (is_object($record) && isset($record->id) && is_numeric($record->id)) {
            $id = (int) $record->id;

            return $id > 0 ? $id : null;
        }

        if (is_array($record) && isset($record['id']) && is_numeric($record['id'])) {
            $id = (int) $record['id'];

            return $id > 0 ? $id : null;
        }

        return null;
    }

    /**
     * @return array{id: mixed, label: string, values: array<string, mixed>}
     */
    protected function mutationRecordPayload(mixed $record): array
    {
        // Save-in-place (Alt+S) needs these so a create form can turn itself
        // into an edit form instead of inserting again on the next save.
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

        if (! empty($values['code']) && ! empty($values['title'])) {
            $label = $values['code'].' — '.$values['title'];
        } elseif (! empty($values['code']) && empty($values['title']) && empty($values['name'])) {
            $label = (string) $values['code'];
        }

        return array_merge([
            'id' => $values['id'] ?? null,
            'label' => (string) $label,
            'values' => $values,
        ], $this->mutationRecordUrls($values['id'] ?? null));
    }

    /**
     * @return array<string, string>
     */
    protected function mutationRecordUrls(mixed $id): array
    {
        if ($id === null || $id === '' || $this->modelName === '') {
            return [];
        }

        $urls = [];

        foreach (['update', 'edit'] as $action) {
            $name = model_route_name($this->modelName, $action);

            if (Route::has($name)) {
                $urls[$action.'_url'] = route($name, $id);
            }
        }

        return $urls;
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
     * the Quick Create form (edit DTO, hideQuick = false), in form order.
     * Prefers entity `code` (BO-1 / BN-1 / …) over raw `id` when available.
     *
     * @return list<string>
     */
    protected function quickCreateSessionColumns(): array
    {
        $columns = array_keys(
            DtoMetadata::for($this->modelRepository->editDto)->quickCreateVisibleFormFields()
        );

        $model = $this->modelRepository->model;
        $usesEntityNumber = in_array(HasEntityNumber::class, class_uses_recursive($model), true);

        if ($usesEntityNumber && ! in_array('code', $columns, true)) {
            array_unshift($columns, 'code');
        }

        if ($columns === []) {
            $columns = $usesEntityNumber ? ['code'] : ['title'];
        }

        return array_values(array_unique($columns));
    }

    private function getData(Request $request)
    {
        $dtoClass = "\\App\\Data\\".$this->modelName.'Data';

        return $dtoClass::from($request->except($this->attachmentRequestKeys()));
    }

    /**
     * @return list<string>
     */
    protected function attachmentRequestKeys(): array
    {
        $keys = [];

        foreach ($this->attachmentFieldNames() as $name) {
            $keys[] = $name;
            $keys[] = 'remove_'.$name;
        }

        return $keys;
    }

    /**
     * @return list<string>
     */
    protected function attachmentFieldNames(): array
    {
        $names = [];
        $dtoClass = $this->modelRepository->editDto ?? '';
        if (! is_string($dtoClass) || $dtoClass === '' || ! class_exists($dtoClass)) {
            return $names;
        }

        foreach (DtoMetadata::for($dtoClass)->formFields() as $name => $args) {
            if (($args[0] ?? '') === 'attachments') {
                $names[] = $name;
            }
        }

        return $names;
    }

    protected function syncAttachments(Request $request, mixed $record, ?int $id = null): void
    {
        if (! AttachableSupport::enabled($this->modelName)) {
            return;
        }

        $id ??= $this->mutationRecordId($record);
        if ($id === null) {
            return;
        }

        $service = app(AttachmentService::class);
        foreach ($this->attachmentFieldNames() as $field) {
            $service->syncFromRequest($this->modelName, $id, $request, $field);
        }
    }

    protected function buildCreateForm(bool $forQuickCreate = false): array
    {
        $dtoClass = $this->modelRepository->editDto;
        $dto = $dtoClass::from($dtoClass::empty());
        $dto = $this->applyStickyContextDefaults($dto);
        $builder = $this->formBuilder();

        return [
            'dto' => $dto,
            'formFields' => $builder->fields($dtoClass, forQuickCreate: $forQuickCreate),
            'hiddenDefaults' => $forQuickCreate
                ? $builder->quickCreateHiddenDefaults($dtoClass, $dto)
                : [],
        ];
    }

    /**
     * Prefill create forms from sticky workspace/project when those fields exist.
     */
    protected function applyStickyContextDefaults(object $dto): object
    {
        $payload = method_exists($dto, 'toArray') ? $dto->toArray() : [];

        if (array_key_exists('project_id', $payload)) {
            $projectId = app(ProjectContext::class)->id();
            if ($projectId !== null && empty($payload['project_id'])) {
                $payload['project_id'] = $projectId;
            }
        }

        if (array_key_exists('workspace_id', $payload)) {
            $workspaceId = app(WorkspaceContext::class)->id();
            if ($workspaceId !== null && empty($payload['workspace_id'])) {
                $payload['workspace_id'] = $workspaceId;
            }
        }

        foreach ([
            'stakeholder_need_id',
            'business_objective_id',
            'primary_business_need_id',
            'feature_id',
            'change_request_id',
        ] as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }
            $value = (int) request()->query($key, 0);
            if ($value > 0 && empty($payload[$key])) {
                $payload[$key] = $value;
            }
        }

        return $dto::from($payload);
    }

    /**
     * Inject sticky project/workspace into the request when the submitted
     * payload omitted them (project is no longer a visible form control).
     */
    protected function mergeStickyContextIntoRequest(Request $request): void
    {
        if (! $request->filled('project_id')) {
            $projectId = app(ProjectContext::class)->id();
            if ($projectId !== null) {
                $request->merge(['project_id' => $projectId]);
            }
        }

        if (! $request->filled('workspace_id')) {
            $workspaceId = app(WorkspaceContext::class)->id();
            if ($workspaceId !== null) {
                $request->merge(['workspace_id' => $workspaceId]);
            }
        }
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
