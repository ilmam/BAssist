<?php

namespace App\Livewire\Concerns;

use App\Support\CrudEntityRegistry;

/**
 * Shared rule for the Livewire pop-ups (delete, view): used only when switched on
 * and when the entity has no controller of its own, because the component works
 * through the repository and would skip that controller's overrides.
 */
trait ChecksDialogSupport
{
    public static function supports(string $model): bool
    {
        return (bool) config('ui.livewire_dialogs', true)
            && empty(CrudEntityRegistry::all()[$model]['controller']);
    }
}
