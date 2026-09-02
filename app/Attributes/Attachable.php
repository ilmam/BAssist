<?php

namespace App\Attributes;

use Attribute;

/**
 * Opt an entity into polymorphic file attachments.
 *
 * Pair with HasAttachments on the model. Put `#[Form('attachments', …)]` on the
 * edit DTO so the control appears on create/edit with the same Form options as
 * other fields (hideQuick, help, uiSpan, section, readonly).
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Attachable {}
