<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasEntityStatus;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[RoutableAttribute]
class Workspace extends BaseModel
{
    use BelongsToTenant;
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

    /** @return list<int> */
    public function owningTenantIds(): array
    {
        return array_values(array_unique(array_map('intval', array_filter([
            $this->getAttribute('tenant_id'),
            $this->getOriginal('tenant_id'),
        ]))));
    }

    public function applyTenantConstraint(Builder $query, int $tenantId): void
    {
        $query->where(function (Builder $q) use ($tenantId): void {
            $q->where($this->qualifyColumn('tenant_id'), $tenantId)
                ->orWhereIn($this->qualifyColumn('id'), Tenancy::grantedWorkspaceIdsQuery());
        });
    }
}
