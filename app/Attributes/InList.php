<?php

namespace App\Attributes;

use Attribute;

/**
 * Marks a property for datatable / list column display.
 *
 * Named InList because `List` is a reserved PHP keyword.
 *
 * On nested Spatie Data relations, the list shows the related record's main
 * display field (name/title/…) — not the foreign key and not the full nested schema.
 *
 * @see Value for detail/view display values (including relation resolution)
 * @see ListForm to declare list + form together
 * @see Form for create/edit controls
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class InList
{
}
