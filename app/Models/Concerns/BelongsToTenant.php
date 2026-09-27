<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;

/**
 * Marks a model as tenant-owned and registers TenantScope.
 *
 * Default ownership: the model has a `project_id` column and belongs to the
 * tenant that owns that project. Override applyTenantConstraint() for models
 * owned differently (Workspace: tenant_id column, Project: workspace_id, …).
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function applyTenantConstraint(Builder $query, int $tenantId): void
    {
        $query->whereIn(
            $this->qualifyColumn('project_id'),
            Tenancy::projectIdsQuery($tenantId)
        );
    }
}
