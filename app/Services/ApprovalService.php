<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\Project;
use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
use App\Support\EntityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Review decisions (#8, BABOK 5.5 Approve Requirements).
 *
 * - Approve: records the decision and moves the item to Agreed.
 * - Request changes: records the reason, posts it as an open comment and moves the item to Need Revision.
 * - A later meaningful edit resets an approval: the item drops back to Draft and needs re-approval.
 */
class ApprovalService
{
    /** Items that go through review. Change requests keep their own approve-and-taint flow. */
    public const APPROVABLE = ['StakeholderNeed', 'Feature', 'FunctionalRequirement', 'NonFunctionalRequirement'];

    /** Columns whose change does NOT reset an approval (workflow / bookkeeping, not content). */
    protected const NON_CONTENT = [
        'status_id', 'priority_id', 'number', 'project_id', 'workspace_id', 'tenant_id',
        'created_by', 'updated_by', 'updated_at', 'created_at', 'sort_order', 'swimlane_flow_step_id',
    ];

    public static function supports(string $model): bool
    {
        return in_array($model, self::APPROVABLE, true) && array_key_exists($model, CrudEntityRegistry::all());
    }

    public static function supportsRecord(Model $record): bool
    {
        return self::supports(class_basename($record));
    }

    public function canApprove(string $model): bool
    {
        return self::supports($model) && entity_can($model, EntityAccess::APPROVE);
    }

    public function record(string $model, int $id): Model
    {
        abort_unless(self::supports($model), 404);
        EntityAccess::authorize(auth()->user(), $model, EntityAccess::VIEW);

        return CrudEntityRegistry::repository($model)->findModel($id);
    }

    /** Latest decision that still covers the current content, if any. */
    public function current(Model $record): ?Approval
    {
        return Approval::query()
            ->active()
            ->where('approvable_type', $record::class)
            ->where('approvable_id', $record->getKey())
            ->with('user')
            ->latest('id')
            ->first();
    }

    /** Most recent decision that was reset by an edit (for "needs re-approval"). */
    public function lastReset(Model $record): ?Approval
    {
        return Approval::query()
            ->whereNotNull('invalidated_at')
            ->where('approvable_type', $record::class)
            ->where('approvable_id', $record->getKey())
            ->with('user')
            ->latest('invalidated_at')
            ->first();
    }

    public function approve(Model $record, ?string $note = null): Approval
    {
        $this->authorizeApprove($record);
        $approval = $this->decide($record, Approval::APPROVED, $note);
        $this->setStatus($record, EntityStatus::AGREED);
        app(ActivityRecorder::class)->log($record, 'approved', null, $note);

        return $approval;
    }

    public function requestChanges(Model $record, string $note): Approval
    {
        $this->authorizeApprove($record);
        $note = trim($note);
        if ($note === '') {
            throw ValidationException::withMessages(['note' => __('ui.review_reason_required')]);
        }

        $approval = $this->decide($record, Approval::CHANGES_REQUESTED, $note);
        $this->setStatus($record, EntityStatus::NEED_REVISION);
        app(ActivityRecorder::class)->log($record, 'changes_requested', null, $note);

        if (CommentService::supports(class_basename($record))) {
            app(CommentService::class)->add($record, __('ui.review_changes_comment', ['note' => $note]));
        }

        return $approval;
    }

    /**
     * Called by the observer after a save: a meaningful content change resets the approval.
     */
    public function handleUpdated(Model $record): void
    {
        $changed = array_diff(array_keys($record->getChanges()), self::NON_CONTENT);
        if ($changed === []) {
            return;
        }

        $current = $this->current($record);
        if ($current === null || ! $current->isApproved()) {
            return;
        }

        $current->forceFill([
            'invalidated_at' => now(),
            'invalidated_reason' => implode(', ', array_slice($changed, 0, 6)),
        ])->save();

        $this->setStatus($record, EntityStatus::DRAFT);
        app(ActivityRecorder::class)->log($record, 'approval_reset', ['fields' => array_values($changed)]);
    }

    /**
     * Items waiting for the current user's review: approvable, not deprecated, no current decision.
     *
     * @return Collection<int, Model>
     */
    public function awaitingReview(int $limit = 6): Collection
    {
        $deprecated = EntityStatus::id(EntityStatus::DEPRECATED);
        $items = collect();

        foreach (self::APPROVABLE as $model) {
            if (! $this->canApprove($model)) {
                continue;
            }
            $class = 'App\\Models\\'.$model;
            $class::query()
                ->with('project')
                ->when($deprecated !== null, fn ($q) => $q->where(fn ($w) => $w->whereNull('status_id')->orWhere('status_id', '!=', $deprecated)))
                ->whereNotExists(function ($q) use ($class): void {
                    $q->selectRaw('1')->from('approvals')
                        ->whereColumn('approvals.approvable_id', (new $class)->qualifyColumn('id'))
                        ->where('approvals.approvable_type', $class)
                        ->whereNull('approvals.invalidated_at');
                })
                ->oldest('updated_at')
                ->limit($limit)
                ->get()
                ->each(fn (Model $m) => $items->push($m));
        }

        return $items->sortBy(fn (Model $m) => $m->updated_at?->getTimestamp() ?? 0)->take($limit)->values();
    }

    /**
     * Current decisions for every approvable item in a project, keyed "Class:id" (PDF sign-off).
     *
     * @return array<string, Approval>
     */
    public function currentForProject(Project $project): array
    {
        $map = [];
        Approval::query()
            ->active()
            ->where('project_id', $project->id)
            ->with('user')
            ->orderBy('id')
            ->get()
            ->each(function (Approval $approval) use (&$map): void {
                $map[$approval->approvable_type.':'.$approval->approvable_id] = $approval; // later ids win
            });

        return $map;
    }

    protected function decide(Model $record, string $decision, ?string $note): Approval
    {
        // A new decision supersedes the previous one.
        Approval::query()
            ->active()
            ->where('approvable_type', $record::class)
            ->where('approvable_id', $record->getKey())
            ->update(['invalidated_at' => now(), 'invalidated_reason' => 'superseded']);

        return Approval::query()->create([
            'project_id' => (int) $record->getAttribute('project_id'),
            'approvable_type' => $record::class,
            'approvable_id' => $record->getKey(),
            'user_id' => auth()->id(),
            'decision' => $decision,
            'note' => filled($note) ? trim((string) $note) : null,
        ]);
    }

    protected function setStatus(Model $record, string $code): void
    {
        $statusId = EntityStatus::id($code);
        if ($statusId === null || ! in_array('status_id', $record->getFillable(), true)) {
            return;
        }
        if ((int) $record->getAttribute('status_id') === $statusId) {
            return;
        }
        // Status is workflow, not content: this save never resets an approval (see NON_CONTENT).
        $record->forceFill(['status_id' => $statusId])->save();
    }

    protected function authorizeApprove(Model $record): void
    {
        EntityAccess::authorize(auth()->user(), class_basename($record), EntityAccess::APPROVE);
    }
}
