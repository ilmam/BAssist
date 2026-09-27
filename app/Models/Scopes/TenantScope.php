<?php

namespace App\Models\Scopes;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Confines every query on a tenant-owned model to the authenticated user's tenant.
 * Applies to finds, lists, route-model binding, relation loads and whereHas.
 * The model decides HOW it is owned via BelongsToTenant::applyTenantConstraint().
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! Tenancy::enforced()) {
            return;
        }

        $tenantId = Tenancy::currentTenantId();

        if ($tenantId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        /** @var Model&BelongsToTenant $model */
        $model->applyTenantConstraint($builder, $tenantId);
    }
}
