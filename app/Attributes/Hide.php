<?php

namespace App\Attributes;

use Attribute;

/**
 * Primary way to exclude a property from detail/value projection.
 *
 * Detail/view discovery includes all public properties by default; mark
 * plumbing and internal fields with #[Hide] (e.g. id, workspace_id,
 * tenant_id, counts, is_orphan). Also excluded from list discovery.
 *
 * Does not affect Form / ListForm (editing). Use Form(hideQuick: true) to hide
 * a field from Quick Create only.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Hide
{
}
