<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\EntityScaffoldTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * entity:materialize-form — regenerate per-entity form blades from DTO metadata.
 * =====================================================================
 *
 * Writes explicit Form::field() lines into the entity's form page and modal
 * fragment, using the same layout shell as the generic templates (form-card,
 * modal-content, open/close, footer buttons) but with owned field markup.
 *
 * Useful when:
 *   - You added/changed Form properties on the edit DTO
 *   - You ejected before and want to refresh forms without touching list/details
 *   - You want hybrid forms without running a full entity:eject
 *
 * @see \App\Support\EntityFormMaterializer
 * @see \App\Console\Commands\EjectEntityCommand
 */
class MaterializeEntityFormCommand extends Command
{
    use EntityScaffoldTrait;

    protected $signature = 'entity:materialize-form
                            {name : Entity model name, e.g. Category}
                            {--force : Overwrite existing form blades}
                            {--dry-run : Preview output without writing files}';

    protected $description = 'Generate per-entity form blades with explicit Form::field lines from DTO metadata';

    public function handle(): int
    {
        $model = Str::studly($this->argument('name'));
        $resource = Str::plural(Str::snake($model));

        if (! $this->entityExists($model)) {
            return self::FAILURE;
        }

        $replace = $this->basicReplacements($model, $resource);
        $files = $this->materializedFormFiles($resource, $replace, $model);

        $this->printPlan($model, $files);

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info("Form blades for [{$model}] would be materialized.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirmIfOverwrites($files)) {
            $this->line('Aborted.');

            return self::FAILURE;
        }

        $this->writeFiles($files);

        $this->newLine();
        $this->info("Materialized form blades for [{$model}].");

        return self::SUCCESS;
    }

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

        $dtoClass = "App\\Data\\{$model}Data";

        if (! class_exists($dtoClass)) {
            $this->error("Edit DTO [{$dtoClass}] does not exist.");

            return false;
        }

        return true;
    }

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

    private function printPlan(string $model, array $files): void
    {
        $this->newLine();
        $this->comment("Materializing form blades for [{$model}]");
        $this->newLine();

        foreach (array_keys($files) as $path) {
            $exists = File::exists($path);
            $this->line(($exists ? '  <comment>overwrite</comment> ' : '  <info>create</info>   ').$path);
        }

        $this->newLine();
    }

    private function confirmIfOverwrites(array $files): bool
    {
        $existing = array_filter(array_keys($files), fn ($p) => File::exists($p));

        if (empty($existing)) {
            return true;
        }

        return $this->confirm(count($existing).' form blade(s) already exist and will be overwritten. Continue?');
    }
}
