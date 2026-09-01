<?php

namespace App\Models\Concerns;

use App\Models\Attachment;
use App\Support\AttachableSupport;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Polymorphic files for an entity. UI and write endpoints stay off until
 * the model is also marked #[Attachable].
 */
trait HasAttachments
{
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderByDesc('id');
    }

    public static function allowsAttachments(): bool
    {
        return AttachableSupport::enabled(static::class);
    }

    protected static function bootHasAttachments(): void
    {
        static::deleting(function (self $model): void {
            if ($model->isForceDeleting()) {
                $model->attachments()->withTrashed()->get()->each->forceDelete();

                return;
            }

            $model->attachments()->get()->each->delete();
        });
    }
}
