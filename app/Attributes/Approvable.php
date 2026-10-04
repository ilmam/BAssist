<?php

namespace App\Attributes;

use Attribute;

/**
 * Opt an entity into review (Approve / Request changes) and the Approve permission
 * column. A later content edit resets the approval. Implies history of the decisions.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Approvable {}
