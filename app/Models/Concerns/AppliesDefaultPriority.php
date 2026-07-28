<?php

namespace App\Models\Concerns;

use App\Support\EntityPriority;

/**
 * Applies EntityPriority::defaultId() (should / MoSCoW) when priority_id is blank on create.
 * Use only on models that have priority_id — not on BaseModel.
 */
trait AppliesDefaultPriority
{
    protected static function bootAppliesDefaultPriority(): void
    {
        static::creating(function (self $model): void {
            if (! blank($model->priority_id)) {
                return;
            }

            $defaultPriorityId = EntityPriority::defaultId();

            if ($defaultPriorityId !== null) {
                $model->priority_id = $defaultPriorityId;
            }
        });
    }
}
