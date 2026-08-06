<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BABOK alignment: Status (requirements lifecycle) and MoSCoW priority apply to
 * Stakeholder Needs, Solution Requirements, and Change Requests — not to
 * Business Objectives (strategic intent) or Business Needs (raw problem/opportunity).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['business_objectives', 'business_needs'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $dropPriority = Schema::hasColumn($tableName, 'priority_id');
            $dropStatus = Schema::hasColumn($tableName, 'status_id');

            if (! $dropPriority && ! $dropStatus) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($dropPriority, $dropStatus) {
                if ($dropPriority) {
                    $table->dropConstrainedForeignId('priority_id');
                }
                if ($dropStatus) {
                    $table->dropConstrainedForeignId('status_id');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['business_objectives', 'business_needs'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $addPriority = ! Schema::hasColumn($tableName, 'priority_id');
            $addStatus = ! Schema::hasColumn($tableName, 'status_id');

            if (! $addPriority && ! $addStatus) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($addPriority, $addStatus) {
                if ($addPriority) {
                    $table->foreignId('priority_id')->nullable()->constrained('priorities');
                }
                if ($addStatus) {
                    $table->foreignId('status_id')->nullable()->constrained('statuses');
                }
            });
        }
    }
};
