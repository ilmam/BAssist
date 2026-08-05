<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align process_steps naming with parent swimlane_flows for already-migrated databases.
 * No-ops when the create migration already used the new names (fresh installs).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('process_steps') && ! Schema::hasTable('swimlane_flow_steps')) {
            Schema::rename('process_steps', 'swimlane_flow_steps');
        }

        $this->renameForeignKeyColumn('features', 'process_step_id', 'swimlane_flow_step_id', 'swimlane_flow_steps');
        $this->renameForeignKeyColumn('functional_requirements', 'process_step_id', 'swimlane_flow_step_id', 'swimlane_flow_steps');
    }

    public function down(): void
    {
        $this->renameForeignKeyColumn('functional_requirements', 'swimlane_flow_step_id', 'process_step_id', 'process_steps');
        $this->renameForeignKeyColumn('features', 'swimlane_flow_step_id', 'process_step_id', 'process_steps');

        if (Schema::hasTable('swimlane_flow_steps') && ! Schema::hasTable('process_steps')) {
            Schema::rename('swimlane_flow_steps', 'process_steps');
        }
    }

    protected function renameForeignKeyColumn(
        string $table,
        string $from,
        string $to,
        string $referencedTable,
    ): void {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (! Schema::hasColumn($table, $from) || Schema::hasColumn($table, $to)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($from) {
            $blueprint->dropForeign([$from]);
        });

        Schema::table($table, function (Blueprint $blueprint) use ($from, $to) {
            $blueprint->renameColumn($from, $to);
        });

        Schema::table($table, function (Blueprint $blueprint) use ($to, $referencedTable) {
            $blueprint->foreign($to)
                ->references('id')
                ->on($referencedTable)
                ->nullOnDelete();
        });
    }
};
