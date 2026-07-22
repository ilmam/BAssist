<?php

namespace App\Attributes;

use Attribute;

/**
 * Declares an Eloquent relation method for framework relation inventory
 * (e.g. RelationsManagerTrait), not DTO list/form/value display.
 *
 * Example:
 *   #[Relation('belongsTo')]
 *   public function project() { ... }
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Relation
{
    public function __construct(
        public string $type,
    ) {}

    public function getRelationType(): string
    {
        return $this->type;
    }
}
