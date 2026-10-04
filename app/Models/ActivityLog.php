<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry in a record's history (#8 activity).
 */
class ActivityLog extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    protected $table = 'activity_log';

    protected $fillable = [
        'project_id',
        'subject_type',
        'subject_id',
        'user_id',
        'event',
        'changes',
        'note',
    ];

    protected function casts(): array
    {
        return ['changes' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applyTenantConstraint(Builder $query, int $tenantId): void
    {
        // Project-less rows (should not happen for spine items) are only visible to their author.
        $query->where(function (Builder $q) use ($tenantId): void {
            $q->whereIn($this->qualifyColumn('project_id'), \App\Support\Tenancy::projectIdsQuery($tenantId))
                ->orWhere(function (Builder $q2): void {
                    $q2->whereNull($this->qualifyColumn('project_id'))->where($this->qualifyColumn('user_id'), auth()->id());
                });
        });
    }
}
