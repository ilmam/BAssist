<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Everything about project grants (invited people) in one place.
 *
 * A grant widens what a person may see (Tenancy) and caps what they may do on
 * a project that is not their own tenant's. Grants are read with the plain
 * query builder so they never recurse into the tenant scope, and never cached,
 * so a changed or revoked grant applies on the next request.
 */
class ProjectAccess
{
    /** Entities a guest never gets, whatever the grant says. */
    public const GUEST_BLOCKED_ENTITIES = ['Workspace', 'Tenant'];

    /** Entities a guest can read but never change. */
    public const GUEST_READ_ONLY_ENTITIES = ['Project', 'Priority', 'Status'];

    public static function levelFor(?User $user, int $projectId): ?ProjectAccessLevel
    {
        if ($user === null) {
            return null;
        }

        $value = DB::table('project_user')
            ->where('user_id', $user->getKey())
            ->where('project_id', $projectId)
            ->value('access');

        return $value === null ? null : ProjectAccessLevel::from($value);
    }

    public static function grantedProjectIdsQuery(User $user): QueryBuilder
    {
        return DB::table('project_user')->select('project_id')->where('user_id', $user->getKey());
    }

    public static function hasAnyGrant(?User $user): bool
    {
        return $user !== null && self::grantedProjectIdsQuery($user)->exists();
    }

    public static function maxLevel(?User $user): ?ProjectAccessLevel
    {
        if ($user === null) {
            return null;
        }

        $levels = DB::table('project_user')->where('user_id', $user->getKey())->pluck('access');

        return $levels
            ->map(fn (string $value) => ProjectAccessLevel::from($value))
            ->sortByDesc(fn (ProjectAccessLevel $level) => $level->rank())
            ->first();
    }

    /** The project belongs to another tenant than the user's (or the user has none). */
    public static function isForeign(User $user, int $projectId): bool
    {
        $tenantId = self::tenantIdOfProject($projectId);

        return $tenantId === null
            || $user->tenant_id === null
            || (int) $user->tenant_id !== $tenantId;
    }

    /**
     * Whether the person may act at this level on the project. On their own
     * tenant's projects the role decides (this answers true); elsewhere only
     * the grant counts.
     */
    public static function can(User $user, int $projectId, ProjectAccessLevel $needed): bool
    {
        if (! self::isForeign($user, $projectId)) {
            return true;
        }

        return self::levelFor($user, $projectId)?->atLeast($needed) ?? false;
    }

    /**
     * Throws unless the signed-in person may act at this level on every one of
     * these projects. Nobody signed in, or tenant isolation off (console,
     * provisioning, platform operator): nothing to check.
     *
     * @param  list<int>  $projectIds
     *
     * @throws AuthorizationException
     */
    public static function assertCan(array $projectIds, ProjectAccessLevel $needed): void
    {
        $user = auth()->user();

        if ($user === null || ! Tenancy::enforced()) {
            return;
        }

        foreach ($projectIds as $projectId) {
            if (! self::can($user, $projectId, $needed)) {
                throw new AuthorizationException('This action is unauthorized.');
            }
        }
    }

    public static function canManageMembers(User $user, Project $project): bool
    {
        if ($user->is_guest) {
            return false;
        }

        if (EntityAccess::isSuperAdmin($user)) {
            return true;
        }

        $tenantId = self::tenantIdOfProject((int) $project->getKey());

        return $tenantId !== null
            && $user->tenant_id !== null
            && (int) $user->tenant_id === $tenantId
            && EntityAccess::can($user, 'Project', EntityAccess::UPDATE);
    }

    /**
     * What a guest may do with an entity type, judged by their highest grant.
     * Which project a write lands in is checked per record (EnforcesProjectAccess).
     */
    public static function guestAllows(User $user, string $entity, string $ability): bool
    {
        if (in_array($entity, self::GUEST_BLOCKED_ENTITIES, true)) {
            return false;
        }

        $max = self::maxLevel($user);

        if ($max === null) {
            return false;
        }

        return match ($ability) {
            EntityAccess::VIEW => true,
            EntityAccess::CREATE, EntityAccess::UPDATE, EntityAccess::DELETE => $max === ProjectAccessLevel::Edit
                && ! in_array($entity, self::GUEST_READ_ONLY_ENTITIES, true),
            default => false,
        };
    }

    protected static function tenantIdOfProject(int $projectId): ?int
    {
        $tenantId = DB::table('projects')
            ->join('workspaces', 'workspaces.id', '=', 'projects.workspace_id')
            ->where('projects.id', $projectId)
            ->value('workspaces.tenant_id');

        return $tenantId === null ? null : (int) $tenantId;
    }
}
