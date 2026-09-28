<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * The value is the id of an existing record of the given model.
 *
 * Unlike Laravel's `exists:table,id`, this goes through Eloquent, so it
 * respects soft deletes and the tenant scope: a record from another tenant
 * does not "exist" for the current user.
 */
class RecordExists implements ValidationRule
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function __construct(public string $modelClass) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $exists = is_numeric($value)
            && (int) $value > 0
            && $this->modelClass::query()->whereKey((int) $value)->exists();

        if (! $exists) {
            $fail('validation.exists')->translate();
        }
    }

    public function __toString(): string
    {
        return 'record_exists:'.class_basename($this->modelClass);
    }
}
