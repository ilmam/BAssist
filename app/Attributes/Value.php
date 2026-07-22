<?php

namespace App\Attributes;

use Attribute;

/**
 * Optional override for which nested display field to use on detail/view projection.
 *
 * Detail/value projection includes all public properties except those marked
 * #[Hide]. Bare #[Value] is not required for inclusion.
 *
 * On a nested Spatie Data relation, optional `$field` overrides the default
 * display heuristic (name → title → category → label → first InList scalar).
 * Matching `*_id` FKs are skipped when the relation property exists.
 *
 * Examples:
 *   // Included by default (no Value needed):
 *   public string $title = '';
 *   public ?ProjectViewData $project = null;   // → project.name
 *
 *   #[Value('code')]
 *   public ?ProjectViewData $project = null;   // → project.code
 *
 *   #[Value(field: 'code')]
 *   public ?ProjectViewData $project = null;   // same override
 *
 * @see Hide to exclude a property from detail/list discovery
 * @see InList for datatable columns
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Value
{
    public function __construct(
        public ?string $field = null,
    ) {}
}
