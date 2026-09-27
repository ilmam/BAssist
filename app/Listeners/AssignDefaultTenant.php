<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\TenancyProvisioner;
use Illuminate\Auth\Events\Authenticated;

/**
 * Give every signed-in user a tenant, so tenant isolation never leaves someone
 * looking at an empty app.
 *
 * A user without a tenant (created by an admin, a seeder or a test) is
 * provisioned the first time they are authenticated, following
 * config('tenancy.mode'): `shared` joins the default "Internal Organization"
 * tenant, `personal` creates the user's own tenant. Turn this off with
 * TENANCY_AUTO_ASSIGN=false to keep tenantless users locked out instead.
 */
class AssignDefaultTenant
{
    public function __construct(protected TenancyProvisioner $provisioner) {}

    public function handle(Authenticated $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || ! config('tenancy.auto_assign', true)) {
            return;
        }

        if ($user->tenant_id !== null && (int) $user->tenant_id > 0) {
            return;
        }

        $this->provisioner->provisionFor($user);
    }
}
