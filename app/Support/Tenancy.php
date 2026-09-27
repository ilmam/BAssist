<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for "which tenant is the current request acting for".
 *
 * Tenant isolation is enforced by App\Models\Scopes\TenantScope (a global scope on
 * every tenant-owned model) and App\Support\TenantPayloadGuard (write payloads).
 * Both ask this class, so the rules live in one place:
 *
 *  - No authenticated user (console, seeders, queue, registration) → not enforced.
 *  - Authenticated user with a tenant → everything is confined to that tenant.
 *  - Authenticated user WITHOUT a tenant → fail closed (sees nothing).
 *  - Exception: a super-admin WITHOUT a tenant is the platform operator and is
 *    not confined (mirrors EntityAccess, where super-admin skips role checks).
 *    A super-admin who belongs to a tenant is confined like everyone else.
 */
class Tenancy
{
    protected static int $bypassDepth = 0;

    /**
     * Whether tenant isolation applies to the current execution context.
     */
    public static function enforced(): bool
    {
        if (self::$bypassDepth > 0) {
            return false;
        }

        if (! Auth::check()) {
            return false;
        }

        return ! (self::currentTenantId() === null && EntityAccess::isSuperAdmin(Auth::user()));
    }

    public static function currentTenantId(): ?int
    {
        $tenantId = Auth::user()?->tenant_id;

        if ($tenantId === null || $tenantId === '' || (int) $tenantId <= 0) {
            return null;
        }

        return (int) $tenantId;
    }

    /**
     * Run a callback with tenant isolation switched off (system provisioning only).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function bypass(callable $callback): mixed
    {
        self::$bypassDepth++;

        try {
            return $callback();
        } finally {
            self::$bypassDepth--;
        }
    }

    /**
     * Sub-select of project ids owned by a tenant (soft-deleted projects and
     * workspaces excluded). Uses the base query builder so it never recurses
     * into Eloquent global scopes.
     */
    public static function projectIdsQuery(int $tenantId): QueryBuilder
    {
        return DB::table('projects')
            ->select('projects.id')
            ->join('workspaces', 'workspaces.id', '=', 'projects.workspace_id')
            ->where('workspaces.tenant_id', $tenantId)
            ->whereNull('projects.deleted_at')
            ->whereNull('workspaces.deleted_at');
    }

    public static function workspaceIdsQuery(int $tenantId): QueryBuilder
    {
        return DB::table('workspaces')
            ->select('workspaces.id')
            ->where('workspaces.tenant_id', $tenantId)
            ->whereNull('workspaces.deleted_at');
    }

    /**
     * Abort with 404 unless the project belongs to the current tenant.
     * Fail-closed: a user without a tenant never passes.
     */
    public static function assertProject(Project $project): void
    {
        if (! self::enforced()) {
            return;
        }

        $tenantId = self::currentTenantId();

        $owned = $tenantId !== null
            && self::projectIdsQuery($tenantId)->where('projects.id', $project->getKey())->exists();

        if (! $owned) {
            abort(404);
        }
    }
}
