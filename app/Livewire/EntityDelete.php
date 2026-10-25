<?php

namespace App\Livewire;

use App\Livewire\Concerns\ChecksDialogSupport;
use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Delete confirmation pop-up. Shows the record, deletes it through the repository,
 * and tells the page to close the pop-up and reload the list (`entity-saved`).
 *
 * Unlike the classic fetch, a record that is already gone, or one that other records
 * still refer to, is explained in the pop-up instead of ending in a browser alert.
 */
class EntityDelete extends Component
{
    use ChecksDialogSupport;

    #[Locked]
    public string $model = '';

    #[Locked]
    public int $recordId = 0;

    public ?string $error = null;

    public function mount(string $model, int $recordId): void
    {
        abort_unless(array_key_exists($model, CrudEntityRegistry::all()), 404);

        $this->model = $model;
        $this->recordId = $recordId;
        $this->authorizeDelete();
    }

    public function delete(): void
    {
        $this->authorizeDelete();
        $this->error = null;

        try {
            CrudEntityRegistry::repository($this->model)->delete($this->recordId);
        } catch (ModelNotFoundException) {
            // Someone else deleted it first: nothing left to do but refresh the list.
        } catch (QueryException) {
            $this->error = __('livewire.delete_blocked');

            return;
        }

        $this->dispatch('entity-saved', model: $this->model, id: $this->recordId);
    }

    public function render(): View
    {
        $dto = null;
        $fields = [];

        try {
            $dto = CrudEntityRegistry::repository($this->model)->getById($this->recordId);
            $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        } catch (ModelNotFoundException) {
            $this->error ??= __('livewire.record_gone');
        }

        return view('livewire.entity-delete', ['dto' => $dto, 'fields' => $fields]);
    }

    protected function authorizeDelete(): void
    {
        EntityAccess::authorize(auth()->user(), $this->model, EntityAccess::DELETE);
    }
}
