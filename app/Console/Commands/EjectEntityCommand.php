<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\EntityScaffoldTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * entity:eject — promote an existing entity up the scaffold ladder.
 * =====================================================================
 *
 * Where make:entity CREATES a brand-new entity at a chosen profile,
 * entity:eject takes an entity that already exists and promotes it one
 * (or more) steps up the ownership ladder, generating only the artifacts
 * that are still missing. It is the "eject" operation: once you eject,
 * you own the generated files and the generic layer stops managing them.
 *
 *   virtual  ──►  hybrid   ──►  material
 *   (no files)  (6 blades)  (blades + controllers)
 *
 * ---------------------------------------------------------------------
 * Level detection (automatic)
 * ---------------------------------------------------------------------
 * The command inspects the filesystem to decide the entity's current
 * level (see detectLevel()):
 *   - a {Model}Controller exists                         → material
 *   - per-entity blades exist (pages/{resource}/list...) → hybrid
 *   - neither                                            → virtual
 *
 * ---------------------------------------------------------------------
 * Promotion behaviour
 * ---------------------------------------------------------------------
 *   Default (one step):
 *     virtual  → hybrid    (creates the six blade files)
 *     hybrid   → material  (creates the two controllers + config wiring)
 *     material → (no-op, already at the top)
 *
 *   --full (straight to the top):
 *     virtual  → material  (blades AND controllers in one run)
 *     hybrid   → material
 *
 * ---------------------------------------------------------------------
 * Arguments and options
 * ---------------------------------------------------------------------
 *   name        (required) Studly-cased model name, e.g. "Country". The
 *               entity's Model and Repository must already exist (created
 *               by make:entity); otherwise the command aborts with a hint.
 *
 *   --full      Eject all the way to material in a single step.
 *   --force     Overwrite files that already exist (skips the interactive
 *               overwrite confirmation).
 *   --dry-run   Print the plan (create/overwrite per file) without writing
 *               anything.
 *
 * ---------------------------------------------------------------------
 * Safety
 * ---------------------------------------------------------------------
 *   - Prints a per-file plan before doing anything.
 *   - When not using --force, prompts for confirmation if any target file
 *     already exists.
 *   - Controllers are generated via Laravel's own make:controller and then
 *     patched to extend CrudController (see EntityScaffoldTrait).
 *   - config/crud.php is only touched when controllers are added, and any
 *     existing nav/home settings on the entry are preserved.
 *
 * ---------------------------------------------------------------------
 * Examples
 * ---------------------------------------------------------------------
 *   php artisan entity:eject Country               # virtual → hybrid
 *   php artisan entity:eject Country --full        # → material in one step
 *   php artisan entity:eject Country --dry-run     # preview only
 *   php artisan entity:eject Country --full --force
 *
 * @see \App\Console\Commands\MakeEntityCommand              Creates the entity first.
 * @see \App\Console\Commands\Concerns\EntityScaffoldTrait   Shared file/config/controller helpers.
 */
class EjectEntityCommand extends Command
{
    use EntityScaffoldTrait;

    protected $signature = 'entity:eject
                            {name : Entity model name, e.g. Country}
                            {--full : Eject all the way to material (blades + controllers) in one step}
                            {--force : Overwrite files that already exist}
                            {--dry-run : Preview what would be created without writing anything}';

    protected $description = 'Promote a virtual entity to hybrid or material by generating its missing artifacts';

    public function handle(): int
    {
        $model    = Str::studly($this->argument('name'));
        $resource = Str::plural(Str::snake($model));

        if (! $this->entityExists($model)) {
            return self::FAILURE;
        }

        $level = $this->detectLevel($model, $resource);

        if ($level === 'material') {
            $this->info("Entity [{$model}] is already at material level — nothing to eject.");

            return self::SUCCESS;
        }

        [$files, $targetLevel] = $this->filesToCreate($model, $resource, $level);

        if (empty($files)) {
            $this->info("Entity [{$model}] is already at [{$targetLevel}] level — nothing to eject.");

            return self::SUCCESS;
        }

        $this->printPlan($model, $level, $targetLevel, $files, $targetLevel === 'material');

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info("Entity [{$model}] would be ejected from [{$level}] to [{$targetLevel}].");
            $this->printNextSteps($model, $targetLevel);

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirmIfOverwrites($files)) {
            $this->line('Aborted.');

            return self::FAILURE;
        }

        $this->writeFiles($files);

        if ($targetLevel === 'material') {
            $this->makeControllers($model);
            $this->addControllersToConfig($model);
        }

        $this->newLine();
        $this->info("Entity [{$model}] ejected from [{$level}] to [{$targetLevel}].");
        $this->printNextSteps($model, $targetLevel);

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Level detection
    // -------------------------------------------------------------------------

    private function detectLevel(string $model, string $resource): string
    {
        $hasController = File::exists(app_path("Http/Controllers/{$model}Controller.php"));
        $hasBlades     = File::exists(resource_path("views/pages/{$resource}/list.blade.php"));

        if ($hasController) {
            return 'material';
        }

        if ($hasBlades) {
            return 'hybrid';
        }

        return 'virtual';
    }

    // -------------------------------------------------------------------------
    // File set resolution
    // -------------------------------------------------------------------------

    /**
     * Returns [blade_files_to_create, target_level].
     * Controllers are generated separately via makeControllers().
     *
     * @return array{array<string,string>, string}
     */
    private function filesToCreate(string $model, string $resource, string $currentLevel): array
    {
        $replace = $this->basicReplacements($model, $resource);
        $full    = $this->option('full');

        $goToMaterial = $full || $currentLevel === 'hybrid';
        $targetLevel  = $goToMaterial ? 'material' : 'hybrid';

        $files = [];

        if ($currentLevel === 'virtual') {
            $files += $this->viewFiles($resource, $replace);
        }

        return [$files, $targetLevel];
    }

    /** Replacements needed by view and controller stubs only — no field metadata required. */
    private function basicReplacements(string $model, string $resource): array
    {
        return [
            'DummyClass'              => $model,
            'DummyResource'           => $resource,
            'DummyTable'              => $resource,
            'DummyDisplayField'       => 'name',
            'DummyFillable'           => '',
            'DummyDataProperties'     => '',
            'DummyViewDataProperties' => '',
            'DummyMigrationColumns'   => '',
        ];
    }

    // -------------------------------------------------------------------------
    // Existence check
    // -------------------------------------------------------------------------

    private function entityExists(string $model): bool
    {
        $modelClass = "App\\Models\\{$model}";

        if (! class_exists($modelClass)) {
            $this->error("Model [{$modelClass}] does not exist. Run [make:entity {$model}] first.");

            return false;
        }

        $repoClass = "App\\Repositories\\{$model}Repository";

        if (! class_exists($repoClass)) {
            $this->error("Repository [{$repoClass}] does not exist. Run [make:entity {$model}] first.");

            return false;
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // config/crud.php
    // -------------------------------------------------------------------------

    /**
     * Add (or merge) the controller/api_controller keys into the config entry
     * for this model, preserving any existing nav/home settings.
     */
    private function addControllersToConfig(string $model): void
    {
        // Read existing config values so we can round-trip nav/home settings.
        $existing = (array) config("crud.models.{$model}", []);

        $entry = $this->buildCrudConfigEntry($model, [
            'controllers' => true,
            'home'        => ! empty($existing['home']),
            'nav'         => ! empty($existing['nav']),
            'nav_label'   => $existing['nav_label']   ?? Str::headline(Str::plural($model)),
            'nav_icon'    => $existing['nav_icon']    ?? 'category',
            'nav_icon_v8' => $existing['nav_icon_v8'] ?? 'category',
        ]);

        $this->updateCrudConfig($model, $entry);
    }

    // -------------------------------------------------------------------------
    // UX helpers
    // -------------------------------------------------------------------------

    private function printPlan(string $model, string $from, string $to, array $files, bool $includeControllers): void
    {
        $this->newLine();
        $this->comment("Ejecting [{$model}]: {$from} → {$to}");
        $this->newLine();

        foreach (array_keys($files) as $path) {
            $exists = File::exists($path);
            $this->line(($exists ? '  <comment>overwrite</comment> ' : '  <info>create</info>   ').$path);
        }

        if ($includeControllers) {
            foreach ([
                app_path("Http/Controllers/{$model}Controller.php"),
                app_path("Http/Controllers/Api/{$model}Controller.php"),
            ] as $path) {
                $exists = File::exists($path);
                $this->line(($exists ? '  <comment>overwrite</comment> ' : '  <info>create</info>   ').$path);
            }
        }

        $this->newLine();
    }

    private function confirmIfOverwrites(array $files): bool
    {
        $existing = array_filter(array_keys($files), fn ($p) => File::exists($p));

        if (empty($existing)) {
            return true;
        }

        return $this->confirm(count($existing).' file(s) already exist and will be overwritten. Continue?');
    }

    private function printNextSteps(string $model, string $level): void
    {
        $this->newLine();
        $this->comment('Next steps:');

        if ($level === 'material') {
            $this->line("  Review App\\Http\\Controllers\\{$model}Controller");
            $this->line("  Review App\\Http\\Controllers\\Api\\{$model}Controller");
            $this->line('  php artisan route:clear');
        } else {
            $resource = Str::plural(Str::snake($model));
            $this->line("  Customise resources/views/pages/{$resource}/");
        }

        $this->newLine();
    }
}
