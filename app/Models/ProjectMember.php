<?php

namespace App\Models;

use App\Services\ProjectReadinessService;
use App\Support\DataVersion;
use App\Support\ProjectAccessLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A grant: one person may use one project at one level. Deliberately not
 * tenant-scoped; the grant is what widens the tenant scope.
 */
class ProjectMember extends Model
{
    protected $table = 'project_user';

    protected $fillable = [
        'project_id',
        'user_id',
        'access',
        'invited_by',
    ];

    /** A grant changes what a person may see, so cached read models must not outlive it. */
    protected static function booted(): void
    {
        $forget = function (self $member): void {
            DataVersion::bump();
            ProjectReadinessService::forget((int) $member->project_id);
        };

        static::saved($forget);
        static::deleted($forget);
    }

    protected function casts(): array
    {
        return ['access' => ProjectAccessLevel::class];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
