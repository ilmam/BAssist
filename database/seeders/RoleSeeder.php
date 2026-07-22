<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed sample roles for local authorization testing.
     */
    public function run(): void
    {
        $manager = Role::updateOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager'],
        );

        $viewer = Role::updateOrCreate(
            ['slug' => 'viewer'],
            ['name' => 'Viewer'],
        );

        $entities = array_keys(CrudEntityRegistry::all());

        foreach ($entities as $entity) {
            RoleEntityPermission::updateOrCreate(
                [
                    'role_id' => $manager->id,
                    'entity' => $entity,
                ],
                [
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => false,
                ],
            );

            RoleEntityPermission::updateOrCreate(
                [
                    'role_id' => $viewer->id,
                    'entity' => $entity,
                ],
                [
                    'can_view' => true,
                    'can_create' => false,
                    'can_update' => false,
                    'can_delete' => false,
                ],
            );
        }

        Role::query()
            ->where('slug', EntityAccess::SUPER_ADMIN_SLUG)
            ->first()
            ?->entityPermissions()
            ->delete();
    }
}
