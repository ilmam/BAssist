<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite does not index foreign keys, so every project- or status-scoped lookup
 * (readiness, matrix, lists) scanned the whole table. Index those columns.
 */
return new class extends Migration
{
    private const COLUMNS = ['project_id', 'status_id', 'priority_id', 'feature_id', 'stakeholder_need_id', 'business_objective_id', 'business_need_id'];

    public function up(): void
    {
        foreach (Schema::getTables() as $table) {
            $name = $table['name'];
            $indexed = collect(Schema::getIndexes($name))->map(fn ($index) => $index['columns'][0] ?? null)->all();

            foreach (Schema::getColumnListing($name) as $column) {
                if (in_array($column, self::COLUMNS, true) && ! in_array($column, $indexed, true)) {
                    Schema::table($name, fn ($t) => $t->index($column, "{$name}_{$column}_idx"));
                }
            }
        }
    }

    public function down(): void
    {
        foreach (Schema::getTables() as $table) {
            $name = $table['name'];
            foreach (Schema::getIndexes($name) as $index) {
                if (str_ends_with($index['name'], '_idx') && str_starts_with($index['name'], $name.'_')) {
                    Schema::table($name, fn ($t) => $t->dropIndex($index['name']));
                }
            }
        }
    }
};
