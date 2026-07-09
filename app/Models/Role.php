<?php

namespace App\Models;

use App\Support\EntityAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function entityPermissions(): HasMany
    {
        return $this->hasMany(RoleEntityPermission::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->slug === EntityAccess::SUPER_ADMIN_SLUG;
    }
}
