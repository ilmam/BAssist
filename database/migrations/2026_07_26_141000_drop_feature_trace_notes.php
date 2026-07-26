<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('features') || ! Schema::hasColumn('features', 'trace_notes')) {
            return;
        }

        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn('trace_notes');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('features') || Schema::hasColumn('features', 'trace_notes')) {
            return;
        }

        Schema::table('features', function (Blueprint $table) {
            $table->text('trace_notes')->nullable()->after('stakeholder_need_id');
        });
    }
};
