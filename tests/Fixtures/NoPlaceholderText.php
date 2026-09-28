<?php

namespace Tests\Fixtures;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Level 2 example: a reusable rule class. */
class NoPlaceholderText implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && preg_match('/\b(TBD|TODO)\b/i', $value)) {
            $fail('The :attribute still contains placeholder text.');
        }
    }
}
