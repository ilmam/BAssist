<?php

namespace Tests;

use App\Support\Validation\EntityValidator;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * The effective validation rules of an edit DTO (inferred + declared), with
     * rule objects rendered as strings, e.g. ['required', 'string', 'max:255'].
     *
     * @param  class-string  $dtoClass
     * @return array<string, list<string>>
     */
    protected function entityRules(string $dtoClass): array
    {
        return array_map(
            fn (array $rules): array => array_map(EntityValidator::describeRule(...), $rules),
            EntityValidator::rulesFor($dtoClass)
        );
    }
}
