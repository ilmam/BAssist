<?php

namespace App\Models;

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
