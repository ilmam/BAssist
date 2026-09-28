<?php

namespace App\Console\Commands;

use App\Attributes\OneOf;
use App\Support\CrudEntityRegistry;
use App\Support\Validation\EntityValidator;
use Illuminate\Console\Command;
use ReflectionClass;
use Spatie\LaravelData\Support\Validation\ValidationRule;

/**
 * entity:rules — print the validation rules the framework enforces for an entity.
 * =====================================================================
 *
 * Shows the effective rules of the entity's edit DTO (what EntityValidator
 * applies on every save) and, per field, which declaration levels contribute:
 *
 *   inferred     from the property type and its #[Form] attribute (level 0)
 *   #[OneOf]     class-level "exactly one of" (level 0)
 *   #[Max] …     Spatie validation attribute on the property (level 1/2)
 *   rules()      the DTO's rules() method (level 3)
 *
 * An after() hook (level 4) is listed at the end when the DTO defines one.
 * See docs/validation.md.
 *
 * ---------------------------------------------------------------------
 * Examples
 * ---------------------------------------------------------------------
 *   php artisan entity:rules FunctionalRequirement
 *   php artisan entity:rules Risk
 */
class EntityRulesCommand extends Command
{
    protected $signature = 'entity:rules {model : Entity model name, e.g. FunctionalRequirement}';

    protected $description = 'Show the validation rules enforced for an entity and where they come from';

    public function handle(): int
    {
        $model = (string) $this->argument('model');

        if (! array_key_exists($model, CrudEntityRegistry::all())) {
            $this->error("Unknown entity [{$model}]. Known: ".implode(', ', array_keys(CrudEntityRegistry::all())));

            return self::FAILURE;
        }

        $dtoClass = CrudEntityRegistry::repository($model)->editDto;
        $this->info("{$model} — {$dtoClass}");

        $rows = [];
        foreach (EntityValidator::rulesFor($dtoClass) as $field => $rules) {
            $rows[] = [
                $field,
                implode(' | ', array_map(EntityValidator::describeRule(...), $rules)),
                implode(', ', $this->sources($dtoClass, (string) $field)),
            ];
        }

        $this->table(['Field', 'Rules', 'Declared by'], $rows);

        if (method_exists($dtoClass, 'after')) {
            $this->line('Plus <comment>after()</comment>: business checks that run once all rules above pass.');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    protected function sources(string $dtoClass, string $field): array
    {
        $sources = [];
        $reflection = new ReflectionClass($dtoClass);
        $property = str_contains($field, '.') ? null : ($reflection->hasProperty($field) ? $reflection->getProperty($field) : null);

        if ($property !== null) {
            $sources[] = 'inferred';

            foreach ($property->getAttributes() as $attribute) {
                if (is_subclass_of($attribute->getName(), ValidationRule::class)) {
                    $sources[] = '#['.class_basename($attribute->getName()).']';
                }
            }
        }

        for ($class = $reflection; $class; $class = $class->getParentClass()) {
            foreach ($class->getAttributes(OneOf::class) as $attribute) {
                if (in_array($field, $attribute->newInstance()->fields, true)) {
                    $sources[] = '#[OneOf]';
                }
            }
        }

        if (method_exists($dtoClass, 'rules') && array_key_exists($field, (array) $dtoClass::rules())) {
            $sources[] = 'rules()';
        }

        return $sources;
    }
}
