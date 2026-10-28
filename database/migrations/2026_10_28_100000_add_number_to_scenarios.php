<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A scenario's code (SC-n) is per project, like the other numbered records, so the
 * exported Gherkin can name each scenario with a tag that never changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenarios', function (Blueprint $table) {
            $table->unsignedInteger('number')->nullable()->after('feature_id');
        });

        // Backfill in creation order, per project. Soft-deleted rows are numbered too
        // so a number is never handed out twice.
        $next = [];
        DB::table('scenarios')
            ->join('features', 'features.id', '=', 'scenarios.feature_id')
            ->orderBy('scenarios.id')
            ->get(['scenarios.id', 'features.project_id'])
            ->each(function ($row) use (&$next): void {
                $next[$row->project_id] = ($next[$row->project_id] ?? 0) + 1;
                DB::table('scenarios')->where('id', $row->id)->update(['number' => $next[$row->project_id]]);
            });
    }

    public function down(): void
    {
        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropColumn('number');
        });
    }
};
