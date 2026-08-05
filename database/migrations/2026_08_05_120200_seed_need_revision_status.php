<?php

use App\Support\EntityStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('statuses')->where('code', EntityStatus::NEED_REVISION)->exists();
        if ($exists) {
            return;
        }

        DB::table('statuses')->insert([
            'code' => EntityStatus::NEED_REVISION,
            'name' => 'Need Revision',
            'sort_order' => 25,
            'description' => 'Flagged by an approved change request; must be kept, revised (via new item), or deprecated.',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        EntityStatus::forgetCache();
    }

    public function down(): void
    {
        DB::table('statuses')->where('code', EntityStatus::NEED_REVISION)->delete();
        EntityStatus::forgetCache();
    }
};
