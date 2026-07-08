<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeEntityCommand extends Command
{
    protected $signature = 'make:entity
                            {name : Entity model name, e.g. Product}
                            {--profile=generic : Scaffold profile: generic, hybrid, or custom}
                            {--fields= : Comma-separated fields, e.g. name:string,description:text,category_id:foreignId:Category:select}
                            {--display= : Field used as display label in selects}
                            {--nav : Add the entity to CRUD navigation config}
                            {--force : Overwrite existing generated files}
                            {--dry-run : Show files that would be generated without writing them}';

    protected $description = 'Scaffold a convention-based CRUD entity';

    private const PROFILES = ['generic', 'hybrid', 'custom'];

    public function handle(): int
    {
        $model = Str::studly($this->argument('name'));
        $profile = strtolower((string) $this->option('profile'));

        if (! in_array($profile, self::PROFILES, true)) {
            $this->error('Invalid profile. Use one of: '.implode(', ', self::PROFILES));

            return self::FAILURE;
        }

        $fields = $this->parseFields($model);
        $displayField = $this->option('display') ?: $fields[0]['name'];

        if (! in_array($displayField, array_column($fields, 'name'), true)) {
            $this->error("Display field [{$displayField}] is not present in --fields.");

            return self::FAILURE;
        }

        $files = $this->buildFiles($model, $profile, $fields, $displayField);
        $this->writeFiles($files);

        if ($this->needsCrudConfig($profile)) {
            $this->updateCrudConfig($model, $profile);
        }

        $this->newLine();
        $this->info("Entity [{$model}] scaffold ".($this->option('dry-run') ? 'previewed' : 'created')." using [{$profile}] profile.");
        $this->printNextSteps($model);

        return self::SUCCESS;
    }

    /**
     * @return list<array{name: string, dbType: string, phpType: string, nullable: bool, formType: string, relation: ?string, default: string}>
     */
    private function parseFields(string $model): array
    {
        $rawFields = trim((string) ($this->option('fields') ?: 'name:string'));
        $fields = [];

        foreach (array_filter(array_map('trim', explode(',', $rawFields))) as $rawField) {
            $parts = array_values(array_filter(array_map('trim', explode(':', $rawField)), static fn ($part) => $part !== ''));

            if (count($parts) < 2) {
                $this->fail("Invalid field [{$rawField}]. Expected name:type.");
            }

            $name = Str::snake(array_shift($parts));
            $dbType = $this->normalizeDbType(array_shift($parts));
            $nullable = str_ends_with($dbType, '?');
            $dbType = rtrim($dbType, '?');
            $formType = null;
            $relation = null;

            foreach ($parts as $part) {
                if ($part === 'nullable') {
                    $nullable = true;
                    continue;
                }

                if ($formType === null && $this->isKnownFormType($part)) {
                    $formType = $part;
                    continue;
                }

                if ($relation === null) {
                    $relation = Str::studly($part);
                    continue;
                }

                if ($formType === null) {
                    $formType = $part;
                }
            }

            if (! preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
                $this->fail("Invalid field name [{$name}]. Use snake_case alphanumeric names.");
            }

            $formType ??= $this->inferFormType($dbType);
            $relation = $dbType === 'foreignId' ? ($relation ?: Str::studly(Str::beforeLast($name, '_id'))) : $relation;
            $phpType = $this->phpTypeFor($dbType, $nullable);

            $fields[] = [
                'name' => $name,
                'dbType' => $dbType,
                'phpType' => $phpType,
                'nullable' => $nullable,
                'formType' => $formType,
                'relation' => $relation,
                'default' => $this->defaultFor($phpType),
            ];
        }

        return $fields;
    }

    private function normalizeDbType(string $type): string
    {
        $nullable = str_ends_with($type, '?') ? '?' : '';
        $type = rtrim($type, '?');

        $normalized = match (strtolower($type)) {
            'int' => 'integer',
            'bool' => 'boolean',
            'biginteger' => 'bigInteger',
            'datetime' => 'dateTime',
            'foreignid' => 'foreignId',
            default => $type,
        };

        $allowed = ['string', 'text', 'integer', 'bigInteger', 'decimal', 'float', 'double', 'boolean', 'date', 'dateTime', 'timestamp', 'foreignId'];
        if (! in_array($normalized, $allowed, true)) {
            $this->fail("Unsupported field type [{$type}].");
        }

        return $normalized.$nullable;
    }

    private function isKnownFormType(string $type): bool
    {
        return in_array($type, ['text', 'textarea', 'select', 'checkbox', 'radio', 'file', 'image', 'dropzone', 'tree', 'date', 'datetime-local', 'number', 'email', 'password'], true);
    }

    private function inferFormType(string $dbType): string
    {
        return match ($dbType) {
            'text' => 'textarea',
            'integer', 'bigInteger', 'decimal', 'float', 'double' => 'number',
            'boolean' => 'checkbox',
            'date' => 'date',
            'dateTime', 'timestamp' => 'datetime-local',
            'foreignId' => 'select',
            default => 'text',
        };
    }

    private function phpTypeFor(string $dbType, bool $nullable): string
    {
        $type = match ($dbType) {
            'integer', 'bigInteger', 'foreignId' => 'int',
            'decimal', 'float', 'double' => 'float',
            'boolean' => 'bool',
            default => 'string',
        };

        return $nullable ? '?'.$type : $type;
    }

    private function defaultFor(string $phpType): string
    {
        return match ($phpType) {
            '?string', '?int', '?float', '?bool' => 'null',
            'int' => '0',
            'float' => '0.0',
            'bool' => 'false',
            default => "''",
        };
    }

    private function buildFiles(string $model, string $profile, array $fields, string $displayField): array
    {
        $resource = Str::plural(Str::snake($model));
        $table = $resource;
        $migrationPath = $this->migrationPath($table);
        $replace = $this->replacements($model, $fields, $displayField, $resource, $table);

        $files = [
            app_path("Models/{$model}.php") => $this->stub('model', $replace),
            app_path("Repositories/{$model}Repository.php") => $this->stub('repository', $replace),
            app_path("Data/{$model}Data.php") => $this->stub('data', $replace),
            app_path("Data/{$model}ViewData.php") => $this->stub('view-data', $replace),
            $migrationPath => $this->stub('migration', $replace),
        ];

        if (in_array($profile, ['hybrid', 'custom'], true)) {
            $files += [
                resource_path("views/pages/{$resource}/list.blade.php") => $this->stub('view-list', $replace),
                resource_path("views/pages/{$resource}/form.blade.php") => $this->stub('view-form', $replace),
                resource_path("views/pages/{$resource}/details.blade.php") => $this->stub('view-details', $replace),
                resource_path("views/pages/{$resource}/modals/view.blade.php") => $this->stub('modal-view', $replace),
                resource_path("views/pages/{$resource}/modals/form.blade.php") => $this->stub('modal-form', $replace),
                resource_path("views/pages/{$resource}/modals/delete.blade.php") => $this->stub('modal-delete', $replace),
            ];
        }

        if ($profile === 'custom') {
            $files += [
                app_path("Http/Controllers/{$model}Controller.php") => $this->stub('controller', $replace),
                app_path("Http/Controllers/Api/{$model}Controller.php") => $this->stub('api-controller', $replace),
            ];
        }

        return $files;
    }

    private function replacements(string $model, array $fields, string $displayField, string $resource, string $table): array
    {
        return [
            'DummyClass' => $model,
            'DummyResource' => $resource,
            'DummyTable' => $table,
            'DummyDisplayField' => $displayField,
            'DummyFillable' => $this->fillableLines($fields),
            'DummyDataProperties' => $this->dataProperties($fields, $displayField),
            'DummyViewDataProperties' => $this->viewDataProperties($fields, $displayField),
            'DummyMigrationColumns' => $this->migrationColumns($fields),
        ];
    }

    private function fillableLines(array $fields): string
    {
        return collect($fields)
            ->map(fn (array $field) => "        '{$field['name']}',")
            ->implode(PHP_EOL);
    }

    private function dataProperties(array $fields, string $displayField): string
    {
        return collect($fields)->map(function (array $field) use ($displayField) {
            $attributes = [
                '        #[ValuePropertyAttribute]',
            ];

            if ($field['name'] === $displayField) {
                array_unshift($attributes, '        #[ListPropertyAttribute]');
            }

            $formArgs = "'{$field['formType']}'";
            if ($field['relation']) {
                $formArgs .= ", '{$field['relation']}'";
            }
            $attributes[] = "        #[FormFieldAttribute({$formArgs})]";

            return implode(PHP_EOL, $attributes).PHP_EOL
                ."        public {$field['phpType']} \${$field['name']} = {$field['default']},";
        })->implode(PHP_EOL);
    }

    private function viewDataProperties(array $fields, string $displayField): string
    {
        return collect($fields)->map(function (array $field) use ($displayField) {
            $attributes = [
                '        #[ValuePropertyAttribute]',
            ];

            if ($field['name'] === $displayField) {
                array_unshift($attributes, '        #[ListPropertyAttribute]');
            }

            return implode(PHP_EOL, $attributes).PHP_EOL
                ."        public {$field['phpType']} \${$field['name']} = {$field['default']},";
        })->implode(PHP_EOL);
    }

    private function migrationColumns(array $fields): string
    {
        return collect($fields)->map(function (array $field) {
            $line = match ($field['dbType']) {
                'decimal' => "\$table->decimal('{$field['name']}', 10, 2)",
                'foreignId' => "\$table->foreignId('{$field['name']}')",
                default => "\$table->{$field['dbType']}('{$field['name']}')",
            };

            if ($field['nullable']) {
                $line .= '->nullable()';
            }

            if ($field['dbType'] === 'foreignId') {
                $line .= '->constrained()';
            }

            return '            '.$line.';';
        })->implode(PHP_EOL);
    }

    private function stub(string $name, array $replace): string
    {
        $path = base_path("stubs/entity/{$name}.stub");
        $contents = File::get($path);

        return str_replace(array_keys($replace), array_values($replace), $contents);
    }

    private function migrationPath(string $table): string
    {
        $existing = glob(database_path("migrations/*_create_{$table}_table.php")) ?: [];

        if ($existing !== []) {
            return $existing[0];
        }

        return database_path('migrations/'.date('Y_m_d_His')."_create_{$table}_table.php");
    }

    private function writeFiles(array $files): void
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
            $this->line((File::exists($path) ? 'Created: ' : 'Created: ').$path);
        }
    }

    private function needsCrudConfig(string $profile): bool
    {
        return $profile === 'custom' || (bool) $this->option('nav');
    }

    private function updateCrudConfig(string $model, string $profile): void
    {
        $path = config_path('crud.php');
        $contents = File::get($path);

        if (str_contains($contents, "'{$model}' =>")) {
            $this->warn("Skipped config/crud.php update because [{$model}] already has an entry.");
            return;
        }

        $entry = $this->crudConfigEntry($model, $profile);
        $needle = "\n        // 'LegacyThing' => ['disabled' => true],";

        if (str_contains($contents, $needle)) {
            $contents = str_replace($needle, $entry.$needle, $contents);
        } else {
            $contents = preg_replace("/\n    ],\n\n];\n?$/", $entry."\n    ],\n\n];\n", $contents);
        }

        if ($this->option('dry-run')) {
            $this->line('Would update: '.$path);
            return;
        }

        File::put($path, $contents);
        $this->line('Updated: '.$path);
    }

    private function crudConfigEntry(string $model, string $profile): string
    {
        $lines = [
            '',
            "        '{$model}' => [",
        ];

        if ($profile === 'custom') {
            $lines[] = "            'controller' => \\App\\Http\\Controllers\\{$model}Controller::class,";
            $lines[] = "            'api_controller' => \\App\\Http\\Controllers\\Api\\{$model}Controller::class,";
        }

        if ($this->option('nav')) {
            $lines[] = "            'nav' => true,";
            $lines[] = "            'nav_label' => '".Str::headline(Str::plural($model))."',";
            $lines[] = "            'nav_icon' => 'category',";
            $lines[] = "            'nav_icon_v8' => 'category',";
        }

        $lines[] = '        ],';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    private function printNextSteps(string $model): void
    {
        $this->newLine();
        $this->comment('Next steps:');
        $this->line('  php artisan migrate');
        $this->line("  php artisan dto:cache-metadata --class=App\\\\Data\\\\{$model}Data");
        $this->line("  php artisan dto:cache-metadata --class=App\\\\Data\\\\{$model}ViewData");
        $this->line('  php artisan data:cache-structure');
        $this->newLine();
        $this->comment('Placeholders for later: add scaffold tests, richer validation rules, factories, seeders, policies, and relation methods.');
    }
}
