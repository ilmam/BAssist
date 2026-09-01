<?php

namespace App\Attributes;

use Attribute;

/**
 * Opt an entity into the shared attachments panel (upload / download / delete).
 *
 * Pair with HasAttachments on the model. Routes exist for every CRUD entity;
 * this marker turns the UI and store/destroy endpoints on.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Attachable {}
