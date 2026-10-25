<?php

namespace App\Livewire\Concerns;

use App\Support\LivewireSwitch;

/**
 * Shared rule for the Livewire pop-ups (delete, view): see LivewireSwitch.
 */
trait ChecksDialogSupport
{
    public static function supports(string $model): bool
    {
        return LivewireSwitch::enabled(LivewireSwitch::DIALOGS, $model);
    }
}
