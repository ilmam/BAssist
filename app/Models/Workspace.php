<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\HasEntityStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[RoutableAttribute]
class Workspace extends BaseModel
{
    use HasEntityStatus;
    use HasFactory;

    protected $displayField = 'name';

    protected $fillable = [
        'name',
        'slug',
        'tenant_id',
        'description',
        'status_id',
    ];

    #[Relation('BelongsTo')]
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    #[Relation('BelongsTo')]
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    #[Relation('HasMany')]
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
