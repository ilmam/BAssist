<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\HasEntityStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[RoutableAttribute]
class Tenant extends BaseModel
{
    use HasEntityStatus;
    use HasFactory;

    protected $displayField = 'name';

    protected $fillable = [
        'name',
        'slug',
        'status_id',
    ];

    #[Relation('BelongsTo')]
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    #[Relation('HasMany')]
    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }

    #[Relation('HasMany')]
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
