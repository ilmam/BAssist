<?php

namespace App\Models;

use App\Attributes\Commentable;
use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Attributes\Tracked;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An activity or deliverable that stakeholders hold a RACI role on.
 */
#[RoutableAttribute]
#[Commentable]
#[Tracked]
class RaciItem extends BaseModel
{
    use BelongsToTenant;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'description',
    ];

    #[Relation('BelongsTo')]
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    #[Relation('HasMany')]
    public function assignments(): HasMany
    {
        return $this->hasMany(RaciAssignment::class);
    }
}
