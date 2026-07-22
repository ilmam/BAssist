<?php

namespace App\Attributes;

use Attribute;

/**
 * Excludes a property from list/value display discovery.
 *
 * Does not affect Form / ListForm (editing). Use Form(hideQuick: true) to hide
 * a field from Quick Create only.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Hide
{
}
