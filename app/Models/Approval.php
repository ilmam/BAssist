<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A review decision on a requirement-level item (#8 approvals).
 * Only the latest non-invalidated decision is "current".
 */
class Approval extends Model
{
    use BelongsToTenant;

    public const APPROVED = 'approved';

    public const CHANGES_REQUESTED = 'changes_requested';

    protected $fillable = [
        'project_id',
        'approvable_type',
        'approvable_id',
        'user_id',
        'decision',
        'note',
        'invalidated_at',
        'invalidated_reason',
    ];

    protected function casts(): array
    {
        return ['invalidated_at' => 'datetime'];
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('invalidated_at');
    }

    public function isApproved(): bool
    {
        return $this->decision === self::APPROVED;
    }
}
