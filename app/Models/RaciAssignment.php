<?php

namespace App\Models;

use App\Attributes\Commentable;
use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Attributes\Tracked;
use App\Models\Concerns\BelongsToTenant;
use App\Support\RaciRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One cell of the RACI matrix: a stakeholder's role on a RACI item.
 */
#[RoutableAttribute]
#[Commentable]
#[Tracked]
class RaciAssignment extends BaseModel
{
    use BelongsToTenant;
    use HasFactory;

    protected $displayField = 'role';

    protected $fillable = [
        'project_id',
        'raci_item_id',
        'stakeholder_id',
        'role',
    ];

    #[Relation('BelongsTo')]
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    #[Relation('BelongsTo')]
    public function raciItem(): BelongsTo
    {
        return $this->belongsTo(RaciItem::class);
    }

    #[Relation('BelongsTo')]
    public function stakeholder(): BelongsTo
    {
        return $this->belongsTo(Stakeholder::class);
    }

    public function roleLabel(): string
    {
        return RaciRole::label($this->role);
    }
}
