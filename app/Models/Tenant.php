<?php

namespace App\Models;

use App\Attributes\RelationAttribute;
use App\Attributes\RoutableAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[RoutableAttribute]
class Tenant extends BaseModel
{
    use HasFactory;

    protected $displayField = 'name';

    protected $fillable = [
        'name',
        'slug',
        'status_id',
    ];

    #[RelationAttribute('BelongsTo')]
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    #[RelationAttribute('HasMany')]
    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }

    #[RelationAttribute('HasMany')]
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
