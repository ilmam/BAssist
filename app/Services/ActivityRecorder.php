<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-record history (#8). Written automatically by RecordActivityObserver for every
 * save of a tracked entity, plus explicit events (approved, changes requested, reset).
 */
class ActivityRecorder
{
    /** Bookkeeping columns never worth showing in history. */
    protected const IGNORED = ['updated_at', 'created_at', 'number', 'created_by', 'updated_by', 'deleted_at'];

    /** Long text is kept short in history; the record itself holds the full value. */
    protected const MAX_VALUE = 240;

    protected bool $paused = false;

    public function pause(callable $callback): mixed
    {
        $this->paused = true;
        try {
            return $callback();
        } finally {
            $this->paused = false;
        }
    }

    /**
     * @param  array<string, mixed>|null  $changes
     */
    public function log(Model $record, string $event, ?array $changes = null, ?string $note = null): void
    {
        if ($this->paused) {
            return;
        }

        ActivityLog::query()->create([
            'project_id' => $record->getAttribute('project_id'),
            'subject_type' => $record::class,
            'subject_id' => $record->getKey(),
            'user_id' => auth()->id(),
            'event' => $event,
            'changes' => $changes,
            'note' => $note,
        ]);
    }

    public function logUpdate(Model $record): void
    {
        $changes = [];
        foreach ($record->getChanges() as $field => $new) {
            if (in_array($field, self::IGNORED, true)) {
                continue;
            }
            $changes[$field] = [$this->short($record->getOriginal($field)), $this->short($new)];
        }

        if ($changes !== []) {
            $this->log($record, 'updated', $changes);
        }
    }

    /**
     * @return Collection<int, ActivityLog>
     */
    public function historyFor(Model $record, int $limit = 50): Collection
    {
        return ActivityLog::query()
            ->where('subject_type', $record::class)
            ->where('subject_id', $record->getKey())
            ->with('user')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    protected function short(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
        if (is_string($value) && mb_strlen($value) > self::MAX_VALUE) {
            return mb_substr($value, 0, self::MAX_VALUE).'…';
        }

        return $value;
    }
}
