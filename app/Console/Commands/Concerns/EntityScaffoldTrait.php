<?php

namespace App\Console\Commands\Concerns;

use App\Support\EntityFormMaterializer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * EntityScaffoldTrait — shared scaffolding helpers for entity commands.
 * =====================================================================
 *
 * Used by both make:entity (MakeEntityCommand) and entity:eject
 * (EjectEntityCommand) so the two commands stay in lock-step: identical
 * file layout, identical config/crud.php mechanics, identical controller
 * generation. If the scaffold conventions change, they change here once.
 *
 * ---------------------------------------------------------------------
 * Host requirements
 * ---------------------------------------------------------------------
 * The consuming command MUST define these options, which the trait reads:
 *   --force     overwrite files that already exist
 *   --dry-run   print intended changes without writing
 *
 * ---------------------------------------------------------------------
 * What it provides
 * ---------------------------------------------------------------------
 *  Stub rendering
 *   - stub($name, $replace)         Load stubs/entity/{name}.stub and
 *                                   apply a placeholder → value map.
 *
 *  File sets (path => rendered contents)
 *   - viewFiles($resource, ...)     The six per-entity blades (list, form,
 *                                   details + view/form/delete modals).
 *
 *  Controller generation (NOT stub-based)
 *   - makeControllers($model)       Delegates to Laravel's make:controller
 *                                   for the web and API controllers, then
 *                                   patchToCrudController() rewrites the
 *                                   generated class to extend CrudController
 *                                   and drops the unused Request import.
 *                                   This avoids maintaining duplicate
 *                                   controller stubs.
 *
 *  Writing
 *   - writeFiles($files)            Honours --force (skip existing unless
 *                                   forced) and --dry-run (report only).
 *
 *  config/crud.php
 *   - buildCrudConfigEntry($model, $options)
 *                                   Build the PHP lines for one model entry
 *                                   (optional controller keys, home, nav
 *                                   label/icons). Emits LF line endings.
 *   - updateCrudConfig($model, $entry)
 *                                   Insert a new entry, or REPLACE an
 *                                   existing one in place. Replacing (vs.
 *                                   skipping) is deliberate: it prevents a
 *                                   stale 'controller' key from a previous
 *                                   material scaffold pointing at a class
 *                                   that no longer exists. Line endings are
 *                                   normalised to LF so the regexes work on
 *                                   both Windows (CRLF) and Unix files.
 *
 * @see \App\Console\Commands\MakeEntityCommand   Creates entities.
 * @see \App\Console\Commands\EjectEntityCommand  Promotes existing entities.
 */
trait EntityScaffoldTrait
{
    // -------------------------------------------------------------------------
    // Stub rendering
    // -------------------------------------------------------------------------

    protected function stub(string $name, array $replace): string
    {
        $path = base_path("stubs/entity/{$name}.stub");
        $contents = File::get($path);

        return str_replace(array_keys($replace), array_values($replace), $contents);
    }

    // -------------------------------------------------------------------------
    // File sets
    // -------------------------------------------------------------------------

    /** @return array<string, string>  path => contents */
    protected function viewFiles(string $resource, array $replace, string $model): array
    {
        $replace += $this->materializedFormReplacements($model);

        return [
            resource_path("views/pages/{$resource}/list.blade.php")         => $this->stub('view-list', $replace),
            resource_path("views/pages/{$resource}/form.blade.php")         => $this->stub('view-form', $replace),
            resource_path("views/pages/{$resource}/details.blade.php")      => $this->stub('view-details', $replace),
            resource_path("views/pages/{$resource}/modals/view.blade.php")  => $this->stub('modal-view', $replace),
            resource_path("views/pages/{$resource}/modals/form.blade.php")  => $this->stub('modal-form', $replace),
            resource_path("views/pages/{$resource}/modals/delete.blade.php") => $this->stub('modal-delete', $replace),
        ];
    }

    /** @return array<string, string>  path => contents */
    protected function materializedFormFiles(string $resource, array $replace, string $model): array
    {
        $replace += $this->materializedFormReplacements($model);

        return [
            resource_path("views/pages/{$resource}/form.blade.php")        => $this->stub('view-form', $replace),
            resource_path("views/pages/{$resource}/modals/form.blade.php") => $this->stub('modal-form', $replace),
        ];
    }

    /** @return array<string, string> */
    protected function materializedFormReplacements(string $model): array
    {
        $materializer = app(EntityFormMaterializer::class);

        return [
            'DummyFormBody'       => $materializer->pageFormBody($model),
            'DummyModalFormBody'  => $materializer->modalFormBody($model),
        ];
    }

    // -------------------------------------------------------------------------
    // Controller generation (delegates to Laravel's make:controller)
    // -------------------------------------------------------------------------

    /**
     * Generate web and API controllers that extend CrudController.
     * Delegates to Laravel's own make:controller, then patches the parent class.
     */
    protected function makeControllers(string $model): void
    {
        $this->makeOneController("{$model}Controller", false);
        $this->makeOneController("Api/{$model}Controller", true);
    }

    private function makeOneController(string $name, bool $inSubNamespace): void
    {
        $path = app_path('Http/Controllers/'.str_replace('/', DIRECTORY_SEPARATOR, $name).'.php');

        if (File::exists($path)) {
            if (! $this->option('force')) {
                $this->warn("Skipped existing file: {$path}");

                return;
            }

            File::delete($path);
        }

        if ($this->option('dry-run')) {
            $this->line('Would create: '.$path);

            return;
        }

        $this->call('make:controller', ['name' => $name]);
        $this->patchToCrudController($path, $inSubNamespace);
    }

    /**
     * Swap the generated `extends Controller` for `extends CrudController`
     * and fix the import statement in the API controller.
     */
    private function patchToCrudController(string $path, bool $inSubNamespace): void
    {
        if (! File::exists($path)) {
            return;
        }

        $contents = File::get($path);

        // Remove the unused Request import that make:controller adds by default.
        $contents = preg_replace('/^use Illuminate\\\\Http\\\\Request;\n/m', '', $contents);

        if ($inSubNamespace) {
            // Api\* controllers need an explicit import; swap Controller for CrudController.
            $contents = str_replace(
                'use App\Http\Controllers\Controller;',
                'use App\Http\Controllers\CrudController;',
                $contents
            );
        }

        $contents = preg_replace('/\bextends Controller\b/', 'extends CrudController', $contents);

        File::put($path, $contents);
    }

    // -------------------------------------------------------------------------
    // Writing
    // -------------------------------------------------------------------------

    protected function writeFiles(array $files): void
    {
        foreach ($files as $path => $contents) {
            if (File::exists($path) && ! $this->option('force')) {
                $this->warn("Skipped existing file: {$path}");
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line((File::exists($path) ? 'Would overwrite: ' : 'Would create: ').$path);
                continue;
            }

            File::ensureDirectoryExists(dirname($path));
            File::put($path, $contents);
            $this->line('Created: '.$path);
        }
    }

    // -------------------------------------------------------------------------
    // config/crud.php helpers
    // -------------------------------------------------------------------------

    /**
     * Insert or replace a model entry in config/crud.php.
     *
     * @param  string  $entry  The pre-built entry string (LF line endings, indented 8 spaces,
     *                          starting with the key line and ending with "],\n").
     */
    protected function updateCrudConfig(string $model, string $entry): void
    {
        $path = config_path('crud.php');

        // Normalise to LF so regex patterns work regardless of OS line endings.
        $contents = str_replace("\r\n", "\n", File::get($path));

        if (str_contains($contents, "'{$model}' =>")) {
            $contents = preg_replace(
                "/\n\n        '{$model}' => \[.*?\],\n/s",
                "\n\n".$entry,
                $contents
            );

            if ($this->option('dry-run')) {
                $this->line('Would replace existing entry in: '.$path);

                return;
            }

            File::put($path, $contents);
            $this->line('Replaced existing entry in: '.$path);

            return;
        }

        $needle = "\n        // 'LegacyThing' => ['disabled' => true],";

        if (str_contains($contents, $needle)) {
            $contents = str_replace($needle, "\n".$entry.$needle, $contents);
        } else {
            $contents = preg_replace("/\n    ],\n\n];\n?$/", "\n\n".$entry."\n    ],\n\n];\n", $contents);
        }

        if ($this->option('dry-run')) {
            $this->line('Would update: '.$path);

            return;
        }

        File::put($path, $contents);
        $this->line('Updated: '.$path);
    }

    /**
     * Build the PHP lines for a config/crud.php model entry.
     *
     * @param  array<string, mixed>  $options  Supported keys:
     *   - controllers (bool)  include 'controller' + 'api_controller' lines
     *   - nav         (bool)
     *   - nav_label   (string)
     *   - nav_icon    (string)
     *   - nav_icon_v8 (string)
     *   - home        (bool)
     */
    protected function buildCrudConfigEntry(string $model, array $options): string
    {
        $lines = ["        '{$model}' => ["];

        if (! empty($options['controllers'])) {
            $lines[] = "            'controller' => \\App\\Http\\Controllers\\{$model}Controller::class,";
            $lines[] = "            'api_controller' => \\App\\Http\\Controllers\\Api\\{$model}Controller::class,";
        }

        if (! empty($options['home'])) {
            $lines[] = "            'home' => true,";
        }

        if (! empty($options['nav'])) {
            $lines[] = "            'nav' => true,";
            $navLabel = $options['nav_label'] ?? Str::headline(Str::plural($model));
            $lines[] = "            'nav_label' => '{$navLabel}',";
            $navIcon   = $options['nav_icon']    ?? 'category';
            $navIconV8 = $options['nav_icon_v8'] ?? 'category';
            $lines[] = "            'nav_icon' => '{$navIcon}',";
            $lines[] = "            'nav_icon_v8' => '{$navIconV8}',";
        }

        $lines[] = '        ],';

        // Always LF — consistent with the LF-normalised file in updateCrudConfig.
        return implode("\n", $lines)."\n";
    }
}
