<?php

namespace App\Support\Validation;

use App\Data\BaseData;
use App\Support\DtoMetadata;
use Illuminate\Validation\ValidationException;

/**
 * The single place where entity saves are validated (web forms, modals, Quick
 * Create, Alt+S save-in-place and the JSON API all go through it).
 *
 * Rules come from the edit DTO: inferred from its properties and #[Form]
 * attributes (FormRuleInferrer), plus any extra levels the DTO declares.
 * See docs/validation.md.
 */
class EntityValidator
{
    /**
     * Controls that legitimately submit nothing when empty (an unchecked box,
     * no new files), so a missing key must not be read as "cleared".
     */
    public const OMITTED_WHEN_EMPTY = ['checkbox', 'attachments', 'file', 'image', 'dropzone'];

    /**
     * Validate the input and build the edit DTO.
     *
     * @template T of BaseData
     *
     * @param  class-string<T>  $dtoClass
     * @param  array<string, mixed>  $input
     * @return T
     *
     * @throws ValidationException
     */
    public static function validate(string $dtoClass, array $input): BaseData
    {
        return $dtoClass::validateAndCreate(self::normalizeInput($dtoClass, $input));
    }

    /**
     * Treat every declared form field that is missing from the input as empty.
     *
     * Spatie Data skips validating a property that has a default value and is
     * absent from the payload. Every edit DTO property has a default, so without
     * this a request that simply leaves out `title` would pass and save ''.
     *
     * @param  class-string<BaseData>  $dtoClass
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeInput(string $dtoClass, array $input): array
    {
        foreach (DtoMetadata::for($dtoClass)->formFields() as $name => $args) {
            if (array_key_exists($name, $input)) {
                continue;
            }

            if (! empty($args['readonly']) || in_array($args[0] ?? '', self::OMITTED_WHEN_EMPTY, true)) {
                continue;
            }

            $input[$name] = null;
        }

        return $input;
    }

    /**
     * Human-readable form of one rule (strings as-is, rule objects by name).
     */
    public static function describeRule(mixed $rule): string
    {
        return match (true) {
            is_string($rule) => $rule,
            $rule instanceof \Closure => 'closure (custom check)',
            $rule instanceof \Stringable, is_object($rule) && method_exists($rule, '__toString') => (string) $rule,
            is_object($rule) => class_basename($rule),
            default => (string) json_encode($rule),
        };
    }

    /**
     * The effective rules for a DTO, for display (entity:rules) and tests.
     *
     * @param  class-string<BaseData>  $dtoClass
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(string $dtoClass): array
    {
        // Mention every property so none is skipped for "absent with a default".
        $payload = [];
        foreach ((new \ReflectionClass($dtoClass))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $payload[$parameter->getName()] = null;
        }

        return $dtoClass::getValidationRules($payload);
    }
}
