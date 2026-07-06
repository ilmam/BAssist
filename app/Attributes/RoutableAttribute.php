<?php

namespace App\Attributes;

use Attribute;

/**
 * Marks an entity (model) as exposed to HTTP CRUD routing.
 *
 * Entities without this attribute may still have a repository for internal
 * data access (forms, relations, jobs) but never receive web/API routes.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class RoutableAttribute
{
    public function __construct(public bool $enabled = true) {}
}
