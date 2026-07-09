<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the default super admin account.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => config('auth.super_admin.email')],
            [
                'name' => config('auth.super_admin.name'),
                'password' => config('auth.super_admin.password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
