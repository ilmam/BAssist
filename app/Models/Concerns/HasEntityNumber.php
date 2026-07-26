<?php

namespace App\Models\Concerns;

use App\Models\Project;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

/**
 * Per-project sequential integer (`number`) with a conventional display prefix.
 * Display as BO-1 / BN-1 / SN-1 via the `code` accessor — prefix is not stored.
 */
trait HasEntityNumber
{
    abstract protected static function entityNumberPrefix(): string;

    public function initializeHasEntityNumber(): void
    {
        $this->mergeFillable(['number']);
        $this->mergeCasts(['number' => 'integer']);
        $this->append('code');
    }

    protected static function bootHasEntityNumber(): void
    {
        static::creating(function (self $model): void {
            if (! blank($model->number)) {
                return;
            }

            $projectId = (int) $model->project_id;

            if ($projectId < 1) {
                return;
            }

            $model->number = static::nextEntityNumber($projectId);
        });

        static::updating(function (self $model): void {
            if (! $model->isDirty('project_id')) {
                return;
            }

            $projectId = (int) $model->project_id;

            if ($projectId < 1) {
                return;
            }

            $model->number = static::nextEntityNumber($projectId);
        });
    }

    protected static function nextEntityNumber(int $projectId): int
    {
        return (int) DB::transaction(function () use ($projectId) {
            // Serialize numbering within a project when concurrent creates race.
            Project::query()->whereKey($projectId)->lockForUpdate()->first();

            return (int) static::withTrashed()
                ->where('project_id', $projectId)
                ->max('number') + 1;
        });
    }

    protected function code(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->number === null) {
                return null;
            }

            return static::entityNumberPrefix().'-'.$this->number;
        });
    }
}
