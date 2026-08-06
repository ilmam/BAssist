<?php

namespace App\Attributes;

use Attribute;

/**
 * Convenience: mark a property as both a list column and a form field.
 *
 * Equivalent to stacking #[InList] and #[Form(...)] with the same arguments.
 * On *ViewData, detail projection includes all non-Hide props by default —
 * ListForm does not require (or imply) #[Value].
 *
 * Examples:
 *   #[ListForm('text')]
 *   public string $title = '';
 *
 *   #[ListForm('select', 'Status', hideQuick: true)]
 *   public ?int $status_id = null;
 *
 *   #[ListForm('select', 'Priority', ktSelect: false)]
 *   public ?int $priority_id = null;
 *
 *   #[ListForm('code', language: 'gherkin', hideQuick: true)]
 *   public ?string $body = null;
 *
 * @see InList
 * @see Form
 * @see Hide
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ListForm
{
    public function __construct(
        public string $type,
        public string $model = '',
        public bool $hideQuick = false,
        public bool $readonly = false,
        public string $language = 'plaintext',
        public string $help = '',
        public ?bool $ktSelect = null,
    ) {}
}
