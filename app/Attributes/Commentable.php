<?php

namespace App\Attributes;

use Attribute;

/**
 * Opt an entity into comment threads (@mentions, resolve) on its view.
 *
 * Nothing else to write: the shared details view shows the Comments section and
 * CommentController accepts the entity. See docs/collaboration.md.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Commentable {}
