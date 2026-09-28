<?php

namespace App\Support\Validation;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Attributes\OneOf;
use App\Rules\RecordExists;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Spatie\LaravelData\Attributes\Validation\Exclude;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Attributes\Validation\Prohibits;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\RequiredWithoutAll;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\RuleInferrers\RuleInferrer;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Types\NamedType;
use Spatie\LaravelData\Support\Validation\PropertyRules;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Level 0 of framework validation: derive rules from what the edit DTO already
 * declares, so entities need no hand-written rules() for ordinary fields.
 *
 * Spatie's own inferrers (registered before this one in config/data.php) add
 * required / nullable and the type (string, integer, boolean, array) from the
 * PHP property. This inferrer adds what only the #[Form] / #[ListForm]
 * attribute knows:
 *
 *  - readonly: true               → excluded from input (never saved from a form)
 *  - single-line text on a string → max:255 (unless the property has #[Max])
 *  - select / kt-select / radio:
 *      model is App\Support\X      → in:<X::values()> (the listed options)
 *      model is App\Models\X       → RecordExists(X) (tenant-aware exists)
 *  - class-level #[OneOf(a, b)]    → exactly one of a / b
 *  - non-nullable `array` property → present (an empty list is a valid value)
 *
 * Rules from Spatie validation attributes (#[Max], #[Rule], …), rules() and the
 * after() hook are added on top. See docs/validation.md for all levels.
 */
class FormRuleInferrer implements RuleInferrer
{
    /** Single-line controls whose string value gets a default length limit. */
    public const SINGLE_LINE_TYPES = ['text', 'email', 'url', 'password', 'tel'];

    public const DEFAULT_MAX_LENGTH = 255;

    /** @var array<class-string, list<OneOf>> */
    protected static array $oneOfCache = [];

    public function handle(DataProperty $property, PropertyRules $rules, ValidationContext $context): PropertyRules
    {
        $this->relaxRequiredList($property, $rules);
        $this->applyOneOf($property, $rules);

        $form = $property->attributes->first(Form::class) ?? $property->attributes->first(ListForm::class);
        if ($form === null) {
            return $rules;
        }

        if ($form->readonly) {
            $rules->add(new Exclude);

            return $rules;
        }

        $type = $form->type;
        $model = $form->model;

        if (in_array($type, self::SINGLE_LINE_TYPES, true)
            && $this->isStringProperty($property)
            && ! $property->attributes->has(Max::class)) {
            $rules->add(new Max(self::DEFAULT_MAX_LENGTH));
        }

        if (in_array($type, ['select', 'kt-select', 'radio'], true) && $model !== '') {
            $this->applyOptionRule($model, $rules);
        }

        return $rules;
    }

    /**
     * Spatie marks a non-nullable `array` as required, which rejects an empty
     * list (e.g. a diagram editor with no rows). Like Spatie's own data
     * collections, a list only has to be present.
     */
    protected function relaxRequiredList(DataProperty $property, PropertyRules $rules): void
    {
        $type = $property->type->type;

        if ($type instanceof NamedType && $type->name === 'array' && $rules->hasType(Required::class)) {
            $rules->removeType(Required::class);
            $rules->prepend(new Present);
        }
    }

    protected function applyOptionRule(string $model, PropertyRules $rules): void
    {
        $listClass = 'App\\Support\\'.$model;
        if (class_exists($listClass) && (method_exists($listClass, 'values') || method_exists($listClass, 'selectOptions'))) {
            $values = method_exists($listClass, 'values')
                ? $listClass::values()
                : array_keys($listClass::selectOptions());
            $rules->add(new In(array_values($values)));

            return;
        }

        $modelClass = 'App\\Models\\'.$model;
        if (class_exists($modelClass) && is_subclass_of($modelClass, Model::class)) {
            $rules->add(new Rule(new RecordExists($modelClass)));
        }
    }

    protected function applyOneOf(DataProperty $property, PropertyRules $rules): void
    {
        foreach ($this->oneOfGroups($property->className) as $group) {
            if (! in_array($property->name, $group->fields, true)) {
                continue;
            }

            $others = array_values(array_diff($group->fields, [$property->name]));
            if ($others === []) {
                continue;
            }

            $rules->add(new RequiredWithoutAll(...$others));
            $rules->add(new Prohibits(...$others));
        }
    }

    /**
     * @return list<OneOf>
     */
    protected function oneOfGroups(string $class): array
    {
        if (! isset(self::$oneOfCache[$class])) {
            $groups = [];
            for ($reflection = new ReflectionClass($class); $reflection; $reflection = $reflection->getParentClass()) {
                foreach ($reflection->getAttributes(OneOf::class) as $attribute) {
                    $groups[] = $attribute->newInstance();
                }
            }
            self::$oneOfCache[$class] = $groups;
        }

        return self::$oneOfCache[$class];
    }

    protected function isStringProperty(DataProperty $property): bool
    {
        return $property->type->acceptsType('string');
    }
}
