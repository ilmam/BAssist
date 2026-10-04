<?php

namespace App\Attributes;

use Attribute;

/**
 * Opt an entity into the History section: who created / changed / deleted the record,
 * with old → new values. The framework attaches TrackedEntityObserver at boot.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Tracked {}
