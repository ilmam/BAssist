<?php

namespace App\Models;

use App\Attributes\RelationAttribute;
use App\Attributes\RoutableAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[RoutableAttribute]
class Workspace extends BaseModel
{
    use HasFactory;

    protected $displayField = 'name';

    protected $fillable = [
        'name',
        'slug',
        'tenant_id',
        'description',
        'status_id',
    ];

    #[RelationAttribute('BelongsTo')]
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    #[RelationAttribute('BelongsTo')]
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    #[RelationAttribute('HasMany')]
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
