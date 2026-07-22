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
 *   using the DTO property default). Default false = show.
 * - $quickSpan: 12-column grid span on Quick Create (1–12). Default 4
 *   (three fields per row on wide layouts). Ignored when $hideQuick is true.
 *   Use 12 for full-width fields (e.g. visible textareas). Do not use 0 to hide.
 *
 * Examples:
 *   #[Form('text')]
 *   #[Form('select', 'Project')]
 *   #[Form('textarea', hideQuick: true)]
 *   #[Form('textarea', quickSpan: 12)]
 *   #[Form('select', 'Priority', quickSpan: 6)]
 *
 * Status/priority defaults: leave null on the DTO. BaseModel applies
 * EntityStatus::defaultId() on create; models with priority_id use
 * AppliesDefaultPriority (EntityPriority::defaultId() = medium).
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
        public int $quickSpan = 4,
    ) {}
}
