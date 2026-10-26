<?php

namespace App\Models\Concerns;

use App\Support\ProjectAccess;
use App\Support\ProjectAccessLevel;

/**
 * Per-record write guard for grants.
 *
 * EntityAccess is global (entity + ability), so it cannot tell that a person
 * may edit project A but only read project B. On saving and deleting this
 * checks the project the record belongs to (and, on update, the one it came
 * from) against the person's grant. It only bites on projects outside the
 * person's own tenant; there the role decides, as before.
 */
trait EnforcesProjectAccess
{
    public static function bootEnforcesProjectAccess(): void
    {
        static::saving(fn ($model) => ProjectAccess::assertCan($model->owningProjectIds(), $model->requiredProjectLevel()));
        static::deleting(fn ($model) => ProjectAccess::assertCan($model->owningProjectIds(), $model->requiredProjectLevel()));
    }

    /**
     * Projects this record belongs to now and (when changing) belonged to before.
     * Models owned another way override this.
     *
     * @return list<int>
     */
    public function owningProjectIds(): array
    {
        return array_values(array_unique(array_map('intval', array_filter([
            $this->getAttribute('project_id'),
            $this->getOriginal('project_id'),
        ]))));
    }

    public function requiredProjectLevel(): ProjectAccessLevel
    {
        return ProjectAccessLevel::Edit;
    }
}
