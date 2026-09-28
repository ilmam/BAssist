<?php
namespace App\Data;

use App\Support\DtoMetadata;
use Illuminate\Validation\Validator;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Data;

/**
 * Base for all edit/view DTOs.
 *
 * Validation (see docs/validation.md): rules are inferred from the properties
 * and their #[Form] attributes. A subclass may add, never has to repeat:
 *  - Spatie validation attributes on a property (#[Max], #[Rule], …)
 *  - static rules(): array — merged on top of the inferred rules
 *    (#[MergeValidationRules]), for rules that relate several fields
 *  - static after(Validator $validator, array $input): void — business checks
 *    that need the database; runs only when all other rules passed
 */
#[MergeValidationRules]
class BaseData extends Data
{
    public static function withValidator(Validator $validator): void
    {
        if (! method_exists(static::class, 'after')) {
            return;
        }

        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            static::after($validator, $validator->getData());
        });
    }

    /**
     * Get array of Value-marked fields using cached DTO metadata.
     * Used for detail views (list columns use InList via listColumns()).
     */
    public function getFields($onlyHeaders = false, $withPrefix = true, $prefix = '', $object = null)
    {
        if ($object == null) {
            $object = $this;
        }

        return DtoMetadata::for($object)->extractValues($object, $onlyHeaders, $withPrefix);
    }

    /**
     * Alias for getFields().
     */
    public function getColumns($onlyHeaders = false, $withPrefix = true, $prefix = '', $object = null)
    {
        return $this->getFields($onlyHeaders, $withPrefix, $prefix, $object);
    }
}
