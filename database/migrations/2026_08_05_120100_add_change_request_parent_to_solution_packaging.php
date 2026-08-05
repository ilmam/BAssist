<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('features', 'change_request_id')) {
            Schema::table('features', function (Blueprint $table) {
                $table->foreignId('change_request_id')
                    ->nullable()
                    ->after('stakeholder_need_id')
                    ->constrained('change_requests')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('functional_requirements', 'change_request_id')) {
            Schema::table('functional_requirements', function (Blueprint $table) {
                $table->foreignId('change_request_id')
                    ->nullable()
                    ->after('stakeholder_need_id')
                    ->constrained('change_requests')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('functional_requirements', 'change_request_id')) {
            Schema::table('functional_requirements', function (Blueprint $table) {
                $table->dropConstrainedForeignId('change_request_id');
            });
        }

        if (Schema::hasColumn('features', 'change_request_id')) {
            Schema::table('features', function (Blueprint $table) {
                $table->dropConstrainedForeignId('change_request_id');
            });
        }
    }
};
