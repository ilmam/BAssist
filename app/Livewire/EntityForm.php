<?php

namespace App\Livewire;

use App\Helpers\FormHelper;
use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
use App\Support\EntityFormBuilder;
use App\Support\LivewireSwitch;
use App\Support\ProjectContext;
use App\Support\WorkspaceContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;
use Livewire\Component;
use App\Support\Validation\EntityValidator;

/**
 * Create / edit form of any CRUD entity, as a Livewire component.
 *
 * It renders the same field controls as `<x-form>` (so every control type, theme
 * and layout is unchanged) and replaces the full-page POST / modal fetch with
 * component actions:
 *
 *  - a field is validated against the DTO's rules when the user leaves it;
 *  - Save validates the whole DTO (`EntityValidator`, same rules as the
 *    controller), writes through the repository, and then either closes the pop-up
 *    and reloads the list (event `entity-saved`) or stays on the page.
 *
 * Entities whose form has file / attachment fields keep the classic form; see
 * `supports()`. Field values travel as plain form data (`livewire-forms.js`), so
 * widgets such as KTSelect and the code editor need no `wire:model`.
 */
class EntityForm extends Component
{
    /** Field types that need a multipart request and so stay on the classic form. */
    protected const UNSUPPORTED_TYPES = ['file', 'image', 'dropzone', 'attachments', 'tree'];

    #[Locked]
    public string $model = '';

    #[Locked]
    public ?int $recordId = null;

    #[Locked]
    public bool $inModal = false;

    /** @var array<string, mixed> */
    public array $values = [];

    /** Flash line after a save that stays on the page. */
    public ?string $status = null;

    /**
     * @param  array<string, mixed>  $values  Initial values (the DTO, already prefilled by the controller).
     */
    public function mount(string $model, array $values = [], ?int $recordId = null, bool $inModal = false): void
    {
        abort_unless(array_key_exists($model, CrudEntityRegistry::all()), 404);

        $this->model = $model;
        $this->recordId = $recordId ?: null;
        $this->inModal = $inModal;
        $this->values = $values;
        $this->authorizeWrite();
    }

    /**
     * Whether an entity's form can run as this component.
     *
     * @param  array<string|int, mixed>  $fields  Output of EntityFormBuilder::fields().
     * @param  string|null  $model  Entity name. An entity with its own controller (config/crud.php
     *                              `controller`) keeps the classic form, because the component saves
     *                              through the repository and would skip that controller's store/update.
     */
    public static function supports(array $fields, ?string $model = null): bool
    {
        if (! LivewireSwitch::enabled(LivewireSwitch::FORMS, $model)) {
            return false;
        }

        foreach ($fields as $field) {
            if (in_array(FormHelper::getFieldType($field), self::UNSUPPORTED_TYPES, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Live check of one field, called when the user leaves it.
     */
    public function validateField(string $name, mixed $value = null): void
    {
        if (! array_key_exists($name, $this->fields())) {
            return;
        }

        $this->values[$name] = $value;
        $this->resetErrorBag($name);

        $rules = EntityValidator::rulesFor($this->dtoClass())[$name] ?? null;
        if ($rules === null) {
            return;
        }

        $validator = Validator::make(
            EntityValidator::normalizeInput($this->dtoClass(), $this->values),
            [$name => $rules]
        );

        if ($validator->fails()) {
            $this->addError($name, (string) $validator->errors()->first($name));
        }
    }

    /**
     * @param  array<string, mixed>  $input  The form as the browser holds it right now.
     */
    public function save(array $input = []): void
    {
        $this->authorizeWrite();
        $this->resetErrorBag();
        $this->status = null;

        $values = array_intersect_key($input, $this->fields()) + $this->values;
        $this->values = $values;

        $creating = $this->recordId === null;
        $repository = $this->repository();
        $payload = EntityValidator::validate($this->dtoClass(), array_merge($this->withStickyContext($values), ['id' => $this->recordId]))->toArray();

        // Same tenant guard as BaseController::store() / update().
        $repository->assertPayloadInTenant($payload);

        if ($this->recordId !== null) {
            $repository->update($this->recordId, $payload);
            $record = $repository->editById($this->recordId);
        } else {
            $record = $repository->create($payload);
            $this->recordId = $this->recordIdOf($record);
        }

        if ($this->inModal) {
            $this->dispatch('entity-saved', model: $this->model, id: $this->recordId);

            return;
        }

        $this->status = __('ui.record_saved');
        $this->dispatch('entity-form-saved');

        // First save of a new record on the full page: continue on its edit page.
        if ($creating) {
            session()->flash('status', __('ui.record_saved'));
            $this->redirect(route(model_route_name($this->model, 'edit'), $this->recordId));
        }
    }

    public function render(): View
    {
        // The component's id wins over the DTO's: after a first save a new record has one.
        $dto = (object) array_merge($this->values, ['id' => $this->recordId]);

        return view('livewire.entity-form', [
            'fields' => $this->fields(),
            'dto' => $dto,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function fields(): array
    {
        return app(EntityFormBuilder::class)->fields($this->dtoClass());
    }

    protected function dtoClass(): string
    {
        return $this->repository()->editDto;
    }

    protected function repository(): \App\Repositories\BaseRepository
    {
        return CrudEntityRegistry::repository($this->model);
    }

    /**
     * Fill project_id / workspace_id from the sticky context when the form did not
     * send them (they are not visible controls), like BaseController does.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function withStickyContext(array $values): array
    {
        if (array_key_exists('project_id', $this->fields()) && blank($values['project_id'] ?? null)) {
            $projectId = app(ProjectContext::class)->id();
            if ($projectId !== null) {
                $values['project_id'] = $projectId;
            }
        }

        if (array_key_exists('workspace_id', $this->fields()) && blank($values['workspace_id'] ?? null)) {
            $workspaceId = app(WorkspaceContext::class)->id();
            if ($workspaceId !== null) {
                $values['workspace_id'] = $workspaceId;
            }
        }

        return $values;
    }

    protected function authorizeWrite(): void
    {
        EntityAccess::authorize(
            auth()->user(),
            $this->model,
            $this->recordId === null ? EntityAccess::CREATE : EntityAccess::UPDATE
        );
    }

    protected function recordIdOf(mixed $record): ?int
    {
        $id = is_object($record) ? ($record->id ?? null) : ($record['id'] ?? null);

        return is_numeric($id) ? (int) $id : null;
    }
}
