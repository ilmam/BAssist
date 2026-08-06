<?php

namespace App\Attributes;

use Attribute;

/**
 * Declares a create/edit form control for a property.
 *
 * Arguments:
 * - $type: control type (text, textarea, code, select, kt-select, …)
 * - $model: related entity name for select options (e.g. 'Project')
 * - $hideQuick: when true, omit from Quick Create UI (submitted as hidden
 *   using the DTO property default). Default false = show.
 * - $readonly: when true, render disabled/readonly (not submitted). Empty
 *   readonly values are omitted from create forms (e.g. code before assign).
 * - $language: syntax language for type=code (e.g. 'gherkin', 'javascript').
 *   Ignored for other types. Default 'plaintext'.
 * - $help: optional short help text shown under the control.
 * - $ktSelect: for type=select only — null uses config('ui.forms.select');
 *   true forces Metronic KTSelect; false forces native kt-input select.
 *   Type kt-select always forces enhanced (same as ktSelect: true).
 * - $section: optional form group key. Use `traceability` to render Need Spine
 *   / lineage fields in the shared bordered panel on create/edit forms.
 * - $uiSpan: rare column span override (int 1–12 for all breakpoints, or
 *   ['sm'=>…,'md'=>…,'lg'=>…]). Null keeps type defaults (half width / full for textarea).
 *
 * Quick Create column spans are theme defaults (sm:12 / md:6 / lg:4;
 * textarea/code/dropzone stay 12) — not set here. Rare overrides use
 * `$field['ui_span']` at form-assembly time.
 *
 * Examples:
 *   #[Form('text')]
 *   #[Form('select', 'Project')]
 *   #[Form('select', 'Project', ktSelect: false)]
 *   #[Form('kt-select', 'Priority')]
 *   #[Form('textarea', hideQuick: true)]
 *   #[Form('code', language: 'gherkin')]
 *   #[Form('code', language: 'javascript', hideQuick: true)]
 *   #[Form('text', hideQuick: true, readonly: true)]
 *   #[Form('select', 'StakeholderNeed', help: 'Need Spine link for the matrix.', section: 'traceability')]
 *   #[Form('radio', 'NeedType')]
 *   #[Form('text', uiSpan: 12)]
 *
 * Status/priority defaults: leave null on the DTO. Models with HasEntityStatus
 * apply EntityStatus::defaultId() on create; models with priority_id use
 * AppliesDefaultPriority (EntityPriority::defaultId() = should / MoSCoW).
 *
 * @see ListForm to also mark the property as a list column
 * @see Value / InList for display (not editing)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Form
{
    public const SECTION_TRACEABILITY = 'traceability';

    public function __construct(
        public string $type,
        public string $model = '',
        public bool $hideQuick = false,
        public bool $readonly = false,
        public string $language = 'plaintext',
        public string $help = '',
        public ?bool $ktSelect = null,
        public string $section = '',
        public int|array|null $uiSpan = null,
    ) {}
}
