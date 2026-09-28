<?php

namespace App\Attributes;

use Attribute;

/**
 * Exactly one of the listed fields must be filled in: at least one, and never
 * two together. Put it on the edit DTO class.
 *
 *   #[OneOf('stakeholder_need_id', 'change_request_id')]
 *   class FunctionalRequirementData extends BaseData
 *
 * The fields stay nullable on the DTO; the framework adds the rules
 * (required_without_all + prohibits) to each of them.
 *
 * @see docs/validation.md
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class OneOf
{
    /** @var list<string> */
    public array $fields;

    public function __construct(string ...$fields)
    {
        $this->fields = array_values($fields);
    }
}
