<?php

namespace App\Models\Concerns;

use App\Support\EntityStatus;
use Illuminate\Validation\ValidationException;

/**
 * Applies EntityStatus::defaultId() (draft) when status_id is blank on create,
 * and exposes draft/agreed/need-revision/deprecated helpers against the shared statuses table.
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

        static::saving(function (self $model): void {
            if (! $model->exists || ! $model->isDirty('status_id') || ! $model->isAgreed()) {
                return;
            }

            $blockers = $model->agreementBlockers();
            if ($blockers !== []) {
                throw ValidationException::withMessages(['status_id' => implode(' ', $blockers)]);
            }
        });
    }

    /**
     * What this record still lacks before it may be agreed (messages, empty when nothing).
     * A model that needs more than a status change overrides this; an existing record cannot
     * become agreed while the list is not empty, whichever way the status is set.
     *
     * @return list<string>
     */
    public function agreementBlockers(): array
    {
        return [];
    }

    public function isDraft(): bool
    {
        return EntityStatus::is(EntityStatus::DRAFT, $this->statusIdOrNull());
    }

    public function isAgreed(): bool
    {
        return EntityStatus::is(EntityStatus::AGREED, $this->statusIdOrNull());
    }

    public function isNeedsRevision(): bool
    {
        return EntityStatus::is(EntityStatus::NEED_REVISION, $this->statusIdOrNull());
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
