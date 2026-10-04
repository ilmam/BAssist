<?php

namespace App\Support;

use App\Models\Role;
use App\Models\RoleEntityPermission;

class RolePermissionSync
{
    /**
     * @param  array<string, array<string, mixed>>  $permissions
     */
    public static function sync(Role $role, array $permissions): void
    {
        if ($role->isSuperAdmin()) {
            return;
        }

        $entities = array_keys(CrudEntityRegistry::all());

        foreach ($entities as $entity) {
            $flags = $permissions[$entity] ?? [];

            $canView = filter_var($flags['view'] ?? false, FILTER_VALIDATE_BOOL);
            $canCreate = filter_var($flags['create'] ?? false, FILTER_VALIDATE_BOOL);
            $canUpdate = filter_var($flags['update'] ?? false, FILTER_VALIDATE_BOOL);
            $canDelete = filter_var($flags['delete'] ?? false, FILTER_VALIDATE_BOOL);
            $canApprove = filter_var($flags['approve'] ?? false, FILTER_VALIDATE_BOOL)
                && EntityFeatures::approvable($entity);

            if (! $canView && ! $canCreate && ! $canUpdate && ! $canDelete && ! $canApprove) {
                $role->entityPermissions()->where('entity', $entity)->delete();

                continue;
            }

            RoleEntityPermission::updateOrCreate(
                [
                    'role_id' => $role->id,
                    'entity' => $entity,
                ],
                [
                    'can_view' => $canView,
                    'can_create' => $canCreate,
                    'can_update' => $canUpdate,
                    'can_delete' => $canDelete,
                    'can_approve' => $canApprove,
                ],
            );
        }
    }

    /**
     * @return array<string, array{view: bool, create: bool, update: bool, delete: bool, approve: bool}>
     */
    public static function matrixFor(Role $role): array
    {
        $matrix = [];

        foreach (CrudEntityRegistry::all() as $model => $options) {
            $permission = $role->entityPermissions->firstWhere('entity', $model);

            $matrix[$model] = [
                'view' => (bool) ($permission?->can_view ?? false),
                'create' => (bool) ($permission?->can_create ?? false),
                'update' => (bool) ($permission?->can_update ?? false),
                'delete' => (bool) ($permission?->can_delete ?? false),
                'approve' => (bool) ($permission?->can_approve ?? false),
            ];
        }

        return $matrix;
    }
}
