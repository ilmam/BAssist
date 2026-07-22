<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\EntityScaffoldTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * make:entity — scaffold a convention-based CRUD entity.
 * =====================================================================
 *
 * Generates every artifact a CRUD entity needs so it is immediately
 * discoverable and routable by the framework's convention layer
 * (see App\Support\CrudEntityRegistry and CrudRouteRegistrar).
 *
 * ---------------------------------------------------------------------
 * The scaffold ladder (profiles)
 * ---------------------------------------------------------------------
 * Profiles describe how much of the entity the framework still manages
 * for you versus how much lives physically on disk and is owned by you:
 *
 *   virtual  ──►  hybrid  ──►  material
 *   (least owned)            (fully owned)
 *
 *   virtual   Backend only: Model, Repository, {Model}Data (edit DTO),
 *             {Model}ViewData (view DTO), and a migration. No blade
 *             files exist — every page and modal is rendered by the
 *             shared templates in resources/views/pages/generic/* and
 *             resources/views/pages/modals/*. Best for simple lookup or
 *             reference tables and for end users who will build their own
 *             pages later. This is the DEFAULT profile.
 *
 *   hybrid    virtual + six per-entity blade files under
 *             resources/views/pages/{resource}/ (list, form, details and
 *             the view/form/delete modals). Form blades are materialized with
 *             explicit Form::field() lines from DTO metadata; list/details
 *             start as copies of the generic templates and can be freely
 *             customised. The shared CrudController still handles requests —
 *             no controller is generated.
 *
 *   material  hybrid + a dedicated {Model}Controller and
 *             Api/{Model}Controller (both extend CrudController), wired
 *             into config/crud.php via the 'controller' and
 *             'api_controller' keys. Full ownership of every layer.
 *
 * ---------------------------------------------------------------------
 * Arguments and options
 * ---------------------------------------------------------------------
 *   name              (required) Studly-cased model name, e.g. "Product".
 *
 *   --profile=        virtual | hybrid | material. Defaults to "virtual".
 *
 *   --fields=         Comma-separated field definitions. Each field is a
 *                     colon-delimited spec:
 *                         name:type
 *                         name:type?                 (nullable shorthand)
 *                         name:type:formType
 *                         name:type:formType:nullable
 *                         name:foreignId:RelatedModel:select
 *                     If omitted, a single "name:string" field is used.
 *                     Supported db types: string, text, integer,
 *                     bigInteger, decimal, float, double, boolean, date,
 *                     dateTime, timestamp, foreignId (plus int/bool/etc.
 *                     aliases). Supported form types: text, textarea,
 *                     select, checkbox, radio, file, image, dropzone,
 *                     tree, date, datetime-local, number, email, password.
 *
 *   --display=        Field used as the display label in select inputs and
 *                     as the list column. Defaults to the first field.
 *                     Must be one of the fields in --fields.
 *
 *   --nav             Force-add the entity to CRUD navigation config.
 *   --no-nav          Do NOT add the entity to navigation (for internal
 *                     entities that should not appear in the menu).
 *                     Navigation is added by default when neither flag is
 *                     given.
 *
 *   --force           Overwrite generated files that already exist.
 *   --dry-run         Print what would be generated without writing.
 *
 * ---------------------------------------------------------------------
 * Examples
 * ---------------------------------------------------------------------
 *   php artisan make:entity Country
 *   php artisan make:entity Product --fields="name:string,price:decimal,description:text?" --display=name
 *   php artisan make:entity Order --profile=material --fields="reference:string,customer_id:foreignId:Customer:select"
 *   php artisan make:entity Log --no-nav --dry-run
 *
 * ---------------------------------------------------------------------
 * config/crud.php handling
 * ---------------------------------------------------------------------
 * When navigation is requested or the material profile is used, the
 * command inserts (or REPLACES, if one already exists) the model's entry
 * in config/crud.php. Replacing rather than skipping prevents stale keys
 * — such as a leftover 'controller' from a previous material scaffold —
 * from pointing at classes that no longer exist. See the
 * EntityScaffoldTrait for the insert/replace mechanics.
 *
 * ---------------------------------------------------------------------
 * After running
 * ---------------------------------------------------------------------
 * The command prints the recommended follow-up steps: run the migration,
 * cache the two DTOs' metadata (dto:cache-metadata) and refresh the
 * spatie/laravel-data structure cache (data:cache-structure).
 *
 * @see \App\Console\Commands\Concerns\EntityScaffoldTrait  Shared file/config helpers.
 * @see \App\Console\Commands\EjectEntityCommand            Promote a virtual entity later.
 * @see \App\Support\CrudEntityRegistry                     Runtime discovery of entities.
 */
class MakeEntityCommand extends Command
{
    use EntityScaffoldTrait;

    protected $signature = 'make:entity
                            {name : Entity model name, e.g. Product}
                            {--profile=virtual : Scaffold profile: virtual, hybrid, or material}
                            {--fields= : Comma-separated fields, e.g. name:string,description:text,category_id:foreignId:Category:select}
                            {--display= : Field used as display label in selects}
                            {--nav : Add the entity to CRUD navigation config}
                            {--no-nav : Do not add the entity to CRUD navigation config}
                            {--force : Overwrite existing generated files}
                            {--dry-run : Show files that would be generated without writing them}';

    protected $description = 'Scaffold a convention-based CRUD entity (virtual | hybrid | material)';

    private const PROFILES = ['virtual', 'hybrid', 'material'];

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

        if ($profile === 'material') {
            $this->makeControllers($model);
        }

        if ($this->needsCrudConfig($profile)) {
            $entry = $this->buildCrudConfigEntry($model, [
                'controllers' => $profile === 'material',
                'nav'         => $this->shouldAddToNavigation(),
                'nav_label'   => Str::headline(Str::plural($model)),
            ]);
            $this->updateCrudConfig($model, $entry);
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
                'name'     => $name,
                'dbType'   => $dbType,
                'phpType'  => $phpType,
                'nullable' => $nullable,
                'formType' => $formType,
                'relation' => $relation,
                'default'  => $this->defaultFor($phpType),
            ];
        }

        return $fields;
    }

    private function normalizeDbType(string $type): string
    {
        $nullable = str_ends_with($type, '?') ? '?' : '';
        $type = rtrim($type, '?');

        $normalized = match (strtolower($type)) {
            'int'         => 'integer',
            'bool'        => 'boolean',
            'biginteger'  => 'bigInteger',
            'datetime'    => 'dateTime',
            'foreignid'   => 'foreignId',
            default       => $type,
        };

        $allowed = ['string', 'text', 'integer', 'bigInteger', 'decimal', 'float', 'double', 'boolean', 'date', 'dateTime', 'timestamp', 'foreignId'];
        if (! in_array($normalized, $allowed, true)) {
            $this->fail("Unsupported field type [{$type}].");
        }

        return $normalized.$nullable;
    }

    private function isKnownFormType(string $type): bool
    {
        return in_array($type, ['text', 'textarea', 'select', 'kt-select', 'checkbox', 'radio', 'file', 'image', 'dropzone', 'tree', 'date', 'datetime-local', 'number', 'email', 'password'], true);
    }

    private function inferFormType(string $dbType): string
    {
        return match ($dbType) {
            'text'                                              => 'textarea',
            'integer', 'bigInteger', 'decimal', 'float', 'double' => 'number',
            'boolean'                                           => 'checkbox',
            'date'                                              => 'date',
            'dateTime', 'timestamp'                            => 'datetime-local',
            'foreignId'                                         => 'select',
            default                                             => 'text',
        };
    }

    private function phpTypeFor(string $dbType, bool $nullable): string
    {
        $type = match ($dbType) {
            'integer', 'bigInteger', 'foreignId' => 'int',
            'decimal', 'float', 'double'          => 'float',
            'boolean'                              => 'bool',
            default                                => 'string',
        };

        return $nullable ? '?'.$type : $type;
    }

    private function defaultFor(string $phpType): string
    {
        return match ($phpType) {
            '?string', '?int', '?float', '?bool' => 'null',
            'int'                                  => '0',
            'float'                                => '0.0',
            'bool'                                 => 'false',
            default                                => "''",
        };
    }

    private function buildFiles(string $model, string $profile, array $fields, string $displayField): array
    {
        $resource = Str::plural(Str::snake($model));
        $table = $resource;
        $migrationPath = $this->migrationPath($table);
        $replace = $this->replacements($model, $fields, $displayField, $resource, $table);

        $files = [
            app_path("Models/{$model}.php")                     => $this->stub('model', $replace),
            app_path("Repositories/{$model}Repository.php")     => $this->stub('repository', $replace),
            app_path("Data/{$model}Data.php")                   => $this->stub('data', $replace),
            app_path("Data/{$model}ViewData.php")               => $this->stub('view-data', $replace),
            $migrationPath                                       => $this->stub('migration', $replace),
        ];

        if (in_array($profile, ['hybrid', 'material'], true)) {
            $files += $this->viewFiles($resource, $replace, $model);
        }

        return $files;
    }

    private function replacements(string $model, array $fields, string $displayField, string $resource, string $table): array
    {
        return [
            'DummyClass'              => $model,
            'DummyResource'           => $resource,
            'DummyTable'              => $table,
            'DummyDisplayField'       => $displayField,
            'DummyFillable'           => $this->fillableLines($model, $fields),
            'DummyDataProperties'     => $this->dataProperties($model, $fields, $displayField),
            'DummyViewDataProperties' => $this->viewDataProperties($model, $fields, $displayField),
            'DummyMigrationColumns'   => $this->migrationColumns($model, $fields),
        ];
    }

    private function fillableLines(string $model, array $fields): string
    {
        $lines = collect($fields)
            ->map(fn (array $field) => "        '{$field['name']}',");

        // Master-data entities manage their own rows; do not auto-attach status_id.
        if (in_array($model, ['Status', 'Priority'], true)) {
            return $lines->implode(PHP_EOL);
        }

        if (! collect($fields)->contains(fn (array $field) => in_array($field['name'], ['status', 'status_id'], true))) {
            $lines->push("        'status_id',");
        }

        return $lines->implode(PHP_EOL);
    }

    private function dataProperties(string $model, array $fields, string $displayField): string
    {
        $properties = collect($fields)->map(function (array $field) use ($displayField) {
            $formArgs = "'{$field['formType']}'";
            if ($field['relation']) {
                $formArgs .= ", '{$field['relation']}'";
            }

            if ($field['name'] === $displayField) {
                $attributes = [
                    "        #[ListForm({$formArgs})]",
                    '        #[Value]',
                ];
            } else {
                $attributes = [
                    '        #[Value]',
                    "        #[Form({$formArgs})]",
                ];
            }

            return implode(PHP_EOL, $attributes).PHP_EOL
                ."        public {$field['phpType']} \${$field['name']} = {$field['default']},";
        });

        if (
            ! in_array($model, ['Status', 'Priority'], true)
            && ! collect($fields)->contains(fn (array $field) => in_array($field['name'], ['status', 'status_id'], true))
        ) {
            $properties->push(
                "        #[ListForm('select', 'Status', hideQuick: true)]\n"
                ."        #[Value]\n"
                ."        public ?int \$status_id = null,"
            );
        }

        return $properties->implode(PHP_EOL);
    }

    private function viewDataProperties(string $model, array $fields, string $displayField): string
    {
        $properties = collect($fields)->flatMap(function (array $field) use ($displayField) {
            $attributes = ['        #[Value]'];

            if ($field['name'] === $displayField) {
                array_unshift($attributes, '        #[InList]');
            }

            $lines = [
                implode(PHP_EOL, $attributes).PHP_EOL
                ."        public {$field['phpType']} \${$field['name']} = {$field['default']},",
            ];

            if ($field['dbType'] === 'foreignId' && $field['relation']) {
                $relationProp = str_ends_with($field['name'], '_id')
                    ? substr($field['name'], 0, -3)
                    : lcfirst($field['relation']);

                $lines[] = "        #[InList]\n"
                    ."        #[Value]\n"
                    ."        public ?\\App\\Data\\{$field['relation']}ViewData \${$relationProp} = null,";
            }

            return $lines;
        });

        if (
            ! in_array($model, ['Status', 'Priority'], true)
            && ! collect($fields)->contains(fn (array $field) => in_array($field['name'], ['status', 'status_id'], true))
        ) {
            $properties->push(
                "        #[Value]\n"
                ."        public ?int \$status_id = null,\n"
                ."        #[InList]\n"
                ."        #[Value]\n"
                ."        public ?\\App\\Data\\StatusViewData \$status = null,"
            );
        }

        return $properties->implode(PHP_EOL);
    }

    private function migrationColumns(string $model, array $fields): string
    {
        $columns = collect($fields)->map(function (array $field) {
            $line = match ($field['dbType']) {
                'decimal'   => "\$table->decimal('{$field['name']}', 10, 2)",
                'foreignId' => "\$table->foreignId('{$field['name']}')",
                default     => "\$table->{$field['dbType']}('{$field['name']}')",
            };

            if ($field['nullable']) {
                $line .= '->nullable()';
            }

            if ($field['dbType'] === 'foreignId') {
                $line .= '->constrained()';
            }

            return '            '.$line.';';
        });

        if (
            ! in_array($model, ['Status', 'Priority'], true)
            && ! collect($fields)->contains(fn (array $field) => in_array($field['name'], ['status', 'status_id'], true))
        ) {
            $columns->push("            \$table->foreignId('status_id')->nullable()->constrained('statuses');");
        }

        return $columns->implode(PHP_EOL);
    }

    private function migrationPath(string $table): string
    {
        $existing = glob(database_path("migrations/*_create_{$table}_table.php")) ?: [];

        if ($existing !== []) {
            return $existing[0];
        }

        return database_path('migrations/'.date('Y_m_d_His')."_create_{$table}_table.php");
    }

    private function needsCrudConfig(string $profile): bool
    {
        return $profile === 'material' || $this->shouldAddToNavigation();
    }

    private function shouldAddToNavigation(): bool
    {
        return ! (bool) $this->option('no-nav');
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
