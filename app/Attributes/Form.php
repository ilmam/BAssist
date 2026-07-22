<?php

namespace App\Attributes;

use Attribute;

/**
 * Declares a create/edit form control for a property.
 *
 * Arguments:
 * - $type: control type (text, textarea, select, …)
 * - $model: related entity name for select options (e.g. 'Project')
 * - $hideQuick: when true, omit from Quick Create UI (submitted as hidden
 *   using $quickDefault or the DTO property default). Default false = show.
 * - $quickDefault: explicit default when $hideQuick is true
 *
 * Examples:
 *   #[Form('text')]
 *   #[Form('select', 'Project')]
 *   #[Form('textarea', hideQuick: true)]
 *   #[Form('text', hideQuick: true, quickDefault: 'problem')]
 *
 * @see ListForm to also mark the property as a list column
 * @see Value / InList for display (not editing)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Form
{
    public function __construct(
        public string $type,
        public string $model = '',
        public bool $hideQuick = false,
        public mixed $quickDefault = null,
    ) {}
}
