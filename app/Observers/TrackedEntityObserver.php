<?php

namespace App\Observers;

use App\Services\ActivityRecorder;
use App\Models\Project;
use App\Services\ApprovalService;
use App\Services\ProjectReadinessService;
use App\Support\DataVersion;
use Illuminate\Database\Eloquent\Model;

/**
 * Records history for tracked entities and resets approvals after meaningful edits (#8).
 * Registered for each entity in AppServiceProvider.
 */
class TrackedEntityObserver
{
    public function created(Model $record): void
    {
        $this->forgetReadiness($record);
        app(ActivityRecorder::class)->log($record, 'created');
    }

    public function updated(Model $record): void
    {
        $this->forgetReadiness($record);
        app(ActivityRecorder::class)->logUpdate($record);

        if (ApprovalService::supportsRecord($record)) {
            app(ApprovalService::class)->handleUpdated($record);
        }
    }

    public function deleted(Model $record): void
    {
        $this->forgetReadiness($record);
        app(ActivityRecorder::class)->log($record, 'deleted');
    }

    private function forgetReadiness(Model $record): void
    {
        DataVersion::bump();

        $projectId = $record instanceof Project ? $record->getKey() : $record->getAttribute('project_id');

        if ($projectId) {
            ProjectReadinessService::forget((int) $projectId);
        }
    }
}
