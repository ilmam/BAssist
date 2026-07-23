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
 * - $readonly: when true, render disabled/readonly (not submitted). Empty
 *   readonly values are omitted from create forms (e.g. code before assign).
 *
 * Quick Create column spans are theme defaults (sm:12 / md:6 / lg:4;
 * textarea/dropzone stay 12) — not set here. Rare overrides use
 * `$field['ui_span']` at form-assembly time.
 *
 * Examples:
 *   #[Form('text')]
 *   #[Form('select', 'Project')]
 *   #[Form('textarea', hideQuick: true)]
 *   #[Form('text', hideQuick: true, readonly: true)]
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
        public bool $readonly = false,
    ) {}
}
