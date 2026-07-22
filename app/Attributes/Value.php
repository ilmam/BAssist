<?php

namespace App\Attributes;

use Attribute;

/**
 * Marks a property as part of the DTO display-value projection.
 *
 * Intent:
 * - Scalar: include this property in detail/view field sets (`getFields()`).
 * - Nested Spatie Data relation: show the related record via a display field
 *   instead of the matching `*_id` foreign key. The FK is skipped when the
 *   relation property exists.
 *
 * Optional `$field` overrides which nested property to show (default heuristic:
 * name → title → category → label → first InList-marked scalar on the nested DTO).
 *
 * Examples:
 *   #[Value]
 *   public string $title = '';
 *
 *   #[Value]
 *   public ?ProjectViewData $project = null;   // → project.name
 *
 *   #[Value('code')]
 *   public ?ProjectViewData $project = null;   // → project.code
 *
 * @see InList for datatable columns
 * @see Hide to exclude a property from display discovery
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Value
{
    public function __construct(
        public ?string $field = null,
    ) {}
}
