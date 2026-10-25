<?php

namespace App\Livewire;

use App\Livewire\Concerns\ChecksDialogSupport;
use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The body of the view pop-up: the record's details, kept current.
 *
 * The component re-renders only when the record has changed (another user saved it,
 * or a form in a pop-up on top of this one saved it). A poll that finds nothing new
 * skips the render, so sections the reader opened or closed are left alone.
 */
class EntityView extends Component
{
    use ChecksDialogSupport;

    #[Locked]
    public string $model = '';

    #[Locked]
    public int $recordId = 0;

    /** Fingerprint of the record as last rendered. */
    public string $version = '';

    public function mount(string $model, int $recordId): void
    {
        abort_unless(array_key_exists($model, CrudEntityRegistry::all()), 404);

        $this->model = $model;
        $this->recordId = $recordId;
        EntityAccess::authorize(auth()->user(), $model, EntityAccess::VIEW);
        $this->version = $this->currentVersion();
    }

    /** Called by wire:poll: render only if the record changed. */
    public function poll(): void
    {
        $version = $this->currentVersion();

        if ($version === $this->version) {
            $this->skipRender();

            return;
        }

        $this->version = $version;
    }

    #[On('entity-saved')]
    public function refresh(): void
    {
        $this->version = $this->currentVersion();
    }

    public function render(): View
    {
        $dto = null;
        $fields = [];

        try {
            $dto = CrudEntityRegistry::repository($this->model)->getById($this->recordId);
            $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        } catch (ModelNotFoundException) {
            // Deleted while the pop-up was open.
        }

        return view('livewire.entity-view', ['dto' => $dto, 'fields' => $fields]);
    }

    protected function currentVersion(): string
    {
        $record = CrudEntityRegistry::repository($this->model)->model::query()->find($this->recordId);

        return $record === null ? 'gone' : (string) $record->updated_at?->getTimestamp();
    }
}
