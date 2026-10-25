<?php

namespace App\Support;

/**
 * The one place that decides whether a screen runs as a Livewire component or as
 * the classic page / pop-up. Most specific wins; any "off" turns it off:
 *
 *   1. master      config('ui.livewire.enabled')   UI_LIVEWIRE=false
 *   2. area        config('ui.livewire.forms')     UI_LIVEWIRE_FORMS=false
 *                  config('ui.livewire.dialogs')   UI_LIVEWIRE_DIALOGS=false
 *   3. entity      config/crud.php  'Model' => ['livewire' => false]            (all areas)
 *                                   'Model' => ['livewire' => ['forms' => false]] (one area)
 *   4. controller  an entity with its own controller ('controller' in config/crud.php)
 *                  is always classic: the components save through the repository
 *                  and would skip that controller's store / update / destroy.
 *
 * Callers: EntityForm::supports(), EntityDelete::supports(), EntityView::supports().
 */
final class LivewireSwitch
{
    public const FORMS = 'forms';

    public const DIALOGS = 'dialogs';

    public static function enabled(string $area, ?string $model = null): bool
    {
        if (! config('ui.livewire.enabled', true) || ! config('ui.livewire.'.$area, true)) {
            return false;
        }

        if ($model === null) {
            return true;
        }

        $options = CrudEntityRegistry::all()[$model] ?? [];

        if (! empty($options['controller'])) {
            return false;
        }

        $entity = $options['livewire'] ?? true;

        if (is_array($entity)) {
            return (bool) ($entity[$area] ?? true);
        }

        return (bool) $entity;
    }

    /**
     * Why the classic screen is used, for `php artisan about`-style diagnostics and tests.
     */
    public static function reason(string $area, ?string $model = null): ?string
    {
        if (! config('ui.livewire.enabled', true)) {
            return 'master switch off (UI_LIVEWIRE)';
        }

        if (! config('ui.livewire.'.$area, true)) {
            return "area switch off (ui.livewire.{$area})";
        }

        if ($model !== null) {
            $options = CrudEntityRegistry::all()[$model] ?? [];

            if (! empty($options['controller'])) {
                return 'entity has its own controller';
            }

            if (! self::enabled($area, $model)) {
                return "entity opted out ('livewire' in config/crud.php)";
            }
        }

        return null;
    }
}
