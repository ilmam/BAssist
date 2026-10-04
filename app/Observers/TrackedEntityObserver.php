<?php

namespace App\Observers;

use App\Services\ActivityRecorder;
use App\Services\ApprovalService;
use Illuminate\Database\Eloquent\Model;

/**
 * Records history for tracked entities and resets approvals after meaningful edits (#8).
 * Registered for each entity in AppServiceProvider.
 */
class TrackedEntityObserver
{
    public function created(Model $record): void
    {
        app(ActivityRecorder::class)->log($record, 'created');
    }

    public function updated(Model $record): void
    {
        app(ActivityRecorder::class)->logUpdate($record);

        if (ApprovalService::supportsRecord($record)) {
            app(ApprovalService::class)->handleUpdated($record);
        }
    }

    public function deleted(Model $record): void
    {
        app(ActivityRecorder::class)->log($record, 'deleted');
    }
}
