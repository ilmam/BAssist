<?php

namespace App\Models\Concerns;

use App\Support\EntityStatus;

/**
 * Applies EntityStatus::defaultId() (draft) when status_id is blank on create,
 * and exposes draft/agreed/deprecated helpers against the shared statuses table.
 *
 * Use only on models that have status_id — not on BaseModel or entities with
 * their own string lifecycle (e.g. Assumption / Constraint / BusinessRule).
 */
trait HasEntityStatus
{
    protected static function bootHasEntityStatus(): void
    {
        static::creating(function (self $model): void {
            if (! blank($model->status_id)) {
                return;
            }

            $defaultStatusId = EntityStatus::defaultId();

            if ($defaultStatusId !== null) {
                $model->status_id = $defaultStatusId;
            }
        });
    }

    public function isDraft(): bool
    {
        return EntityStatus::is(EntityStatus::DRAFT, $this->statusIdOrNull());
    }

    public function isAgreed(): bool
    {
        return EntityStatus::is(EntityStatus::AGREED, $this->statusIdOrNull());
    }

    public function isDeprecated(): bool
    {
        return EntityStatus::is(EntityStatus::DEPRECATED, $this->statusIdOrNull());
    }

    protected function statusIdOrNull(): ?int
    {
        return $this->status_id === null ? null : (int) $this->status_id;
    }
}
