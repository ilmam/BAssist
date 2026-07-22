<?php

namespace App\Attributes;

use Attribute;

/**
 * Convenience: mark a property as both a list column and a form field.
 *
 * Equivalent to stacking #[InList] and #[Form(...)] with the same arguments.
 * Does not imply #[Value] — add Value separately when the property belongs
 * in detail/view projections.
 *
 * Examples:
 *   #[ListForm('text')]
 *   #[Value]
 *   public string $title = '';
 *
 *   #[ListForm('select', 'Status', hideQuick: true)]
 *   public ?int $status_id = null;
 *
 * @see InList
 * @see Form
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ListForm
{
    public function __construct(
        public string $type,
        public string $model = '',
        public bool $hideQuick = false,
        public mixed $quickDefault = null,
    ) {}
}
