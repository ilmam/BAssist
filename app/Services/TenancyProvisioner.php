<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Workspace;
use App\Support\EntityStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenancyProvisioner
{
    public function provisionFor(User $user): User
    {
        return DB::transaction(function () use ($user) {
            if (config('tenancy.mode') === 'shared') {
                return $this->attachToSharedTenant($user);
            }

            return $this->createPersonalTenant($user);
        });
    }

    public function ensureSharedTenant(): Tenant
    {
        $slug = config('tenancy.shared.tenant_slug');

        return Tenant::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => config('tenancy.shared.tenant_name'),
                'status_id' => EntityStatus::id(EntityStatus::AGREED),
            ],
        );
    }

    public function ensureSharedWorkspace(Tenant $tenant): Workspace
    {
        $slug = config('tenancy.shared.workspace_slug');

        return Workspace::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'slug' => $slug,
            ],
            [
                'name' => config('tenancy.shared.workspace_name'),
                'status_id' => EntityStatus::id(EntityStatus::AGREED),
            ],
        );
    }

    protected function attachToSharedTenant(User $user): User
    {
        $tenant = $this->ensureSharedTenant();
        $workspace = $this->ensureSharedWorkspace($tenant);

        $user->forceFill([
            'tenant_id' => $tenant->id,
            'workspace_id' => $workspace->id,
        ])->save();

        return $user->refresh();
    }

    protected function createPersonalTenant(User $user): User
    {
        $baseSlug = Str::slug($user->name) ?: 'user';
        $slug = $baseSlug.'-'.$user->id;

        $tenant = Tenant::query()->create([
            'name' => $user->name."'s Organization",
            'slug' => $slug,
            'status_id' => EntityStatus::id(EntityStatus::AGREED),
        ]);

        $workspace = Workspace::query()->create([
            'tenant_id' => $tenant->id,
            'name' => config('tenancy.personal.workspace_name'),
            'slug' => config('tenancy.personal.workspace_slug'),
            'status_id' => EntityStatus::id(EntityStatus::AGREED),
        ]);

        $user->forceFill([
            'tenant_id' => $tenant->id,
            'workspace_id' => $workspace->id,
        ])->save();

        return $user->refresh();
    }
}
