<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Support\EntityAccess;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the default super admin account.
     */
    public function run(): void
    {
        $role = Role::updateOrCreate(
            ['slug' => EntityAccess::SUPER_ADMIN_SLUG],
            ['name' => 'Super Admin'],
        );

        User::updateOrCreate(
            ['email' => config('auth.super_admin.email')],
            [
                'name' => config('auth.super_admin.name'),
                'password' => config('auth.super_admin.password'),
                'email_verified_at' => now(),
                'role_id' => $role->id,
            ],
        );
    }
}
