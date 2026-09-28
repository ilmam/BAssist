<?php

namespace App\Support\Validation;

/**
 * Browser-side hints derived from the same rules the server enforces, so the
 * form marks required fields and caps lengths without any per-page code.
 *
 * Hints are only a convenience: the server (EntityValidator) is the authority.
 * `required` is only emitted for native, visible controls (text, textarea,
 * number). Enhanced controls (code editors, searchable selects) hide their
 * native element, and a browser cannot focus a hidden required field.
 */
class ValidationHints
{
    public const REQUIRED_HINT_TYPES = ['text', 'textarea', 'number', 'email', 'url', 'tel', 'password'];

    public const MAXLENGTH_HINT_TYPES = ['text', 'textarea', 'email', 'url', 'tel', 'password'];

    /** @var array<class-string, array<string, array{required: bool, maxlength: ?int}>> */
    protected static array $cache = [];

    /**
     * @param  class-string  $dtoClass
     * @return array<string, array{required: bool, maxlength: ?int}>
     */
    public static function for(string $dtoClass): array
    {
        if (isset(self::$cache[$dtoClass])) {
            return self::$cache[$dtoClass];
        }

        $hints = [];

        foreach (EntityValidator::rulesFor($dtoClass) as $field => $rules) {
            if (str_contains((string) $field, '.')) {
                continue;
            }

            $strings = array_values(array_filter((array) $rules, 'is_string'));
            $maxlength = null;

            foreach ($strings as $rule) {
                if (str_starts_with($rule, 'max:')) {
                    $maxlength = (int) substr($rule, 4);
                }
            }

            $hints[$field] = [
                'required' => in_array('required', $strings, true),
                'maxlength' => $maxlength,
            ];
        }

        return self::$cache[$dtoClass] = $hints;
    }

    /**
     * Add `required` / `maxlength` to form field definitions (EntityFormBuilder).
     *
     * @param  class-string  $dtoClass
     * @param  array<string, array<int|string, mixed>>  $fields
     * @return array<string, array<int|string, mixed>>
     */
    public static function apply(string $dtoClass, array $fields): array
    {
        $hints = self::for($dtoClass);

        foreach ($fields as $name => &$field) {
            $type = (string) ($field[0] ?? '');
            $hint = $hints[$name] ?? null;

            if ($hint === null || ! empty($field['readonly'])) {
                continue;
            }

            if ($hint['required'] && in_array($type, self::REQUIRED_HINT_TYPES, true)) {
                $field['required'] = true;
            }

            if ($hint['maxlength'] !== null && in_array($type, self::MAXLENGTH_HINT_TYPES, true)) {
                $field['maxlength'] = $hint['maxlength'];
            }
        }

        return $fields;
    }
}
