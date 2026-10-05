<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A discussion comment on any spine / guardrail entity. A root comment (parent_id null)
 * is a thread and carries its open / resolved state; replies hang off the root.
 */
class Comment extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'commentable_type',
        'commentable_id',
        'parent_id',
        'user_id',
        'body',
        'via',
        'status',
        'implemented_at',
        'implemented_by',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'implemented_at' => 'datetime',
        ];
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    public function mentionedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'comment_mentions')->withPivot('seen_at')->withTimestamps();
    }

    public function isOpen(): bool
    {
        return $this->resolved_at === null;
    }

    public function scopeThreads(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /** Not closed: open, answered or implemented. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    /** @param  string|list<string>  $status */
    public function scopeInStatus(Builder $query, string|array $status): Builder
    {
        return $query->whereIn('status', (array) $status);
    }

    /** The thread's status; threads from before statuses existed fall back on resolved_at. */
    public function currentStatus(): string
    {
        return $this->status ?? ($this->resolved_at !== null ? \App\Support\CommentStatus::CLOSED : \App\Support\CommentStatus::OPEN);
    }

    public function implementer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'implemented_by');
    }
}
