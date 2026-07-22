<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('description');
        });

        Schema::table('priorities', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('description');
        });

        // Backfill EntityStatus / EntityPriority allowlist rows as system-locked.
        DB::table('statuses')
            ->whereIn('code', ['draft', 'agreed', 'deprecated'])
            ->update(['is_system' => true]);

        DB::table('priorities')
            ->whereIn('code', ['high', 'medium', 'low'])
            ->update(['is_system' => true]);
    }

    public function down(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });

        Schema::table('priorities', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
