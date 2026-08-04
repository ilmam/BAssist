<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $legacyColumns = [
        'business_objective_id',
        'business_need_id',
        'stakeholder_need_id',
        'feature_id',
        'functional_requirement_id',
    ];

    public function up(): void
    {
        $this->recoverFailedSqliteRebuild();

        if (! Schema::hasColumn('change_requests', 'affected_type')) {
            Schema::table('change_requests', function (Blueprint $table) {
                $table->string('affected_type')->nullable()->after('impact_notes');
                $table->unsignedBigInteger('affected_id')->nullable()->after('affected_type');
                $table->index(['affected_type', 'affected_id']);
            });
        }

        $this->backfillAffectedSubject();
        $this->dropLegacyIndexes();
        $this->dropLegacyColumns();
    }

    public function down(): void
    {
        foreach ($this->legacyColumns as $column) {
            if (Schema::hasColumn('change_requests', $column)) {
                continue;
            }

            $tableName = match ($column) {
                'business_objective_id' => 'business_objectives',
                'business_need_id' => 'business_needs',
                'stakeholder_need_id' => 'stakeholder_needs',
                'feature_id' => 'features',
                'functional_requirement_id' => 'functional_requirements',
            };

            Schema::table('change_requests', function (Blueprint $table) use ($column, $tableName) {
                $table->foreignId($column)->nullable()->constrained($tableName)->nullOnDelete();
            });
        }

        if (Schema::hasColumn('change_requests', 'affected_type')) {
            $rows = DB::table('change_requests')->select(['id', 'affected_type', 'affected_id'])->get();

            foreach ($rows as $row) {
                if ($row->affected_type === null || $row->affected_id === null) {
                    continue;
                }

                $column = match ($row->affected_type) {
                    'business_objective' => 'business_objective_id',
                    'business_need' => 'business_need_id',
                    'stakeholder_need' => 'stakeholder_need_id',
                    'feature' => 'feature_id',
                    'functional_requirement' => 'functional_requirement_id',
                    default => null,
                };

                if ($column === null || ! Schema::hasColumn('change_requests', $column)) {
                    continue;
                }

                DB::table('change_requests')->where('id', $row->id)->update([
                    $column => $row->affected_id,
                ]);
            }

            Schema::table('change_requests', function (Blueprint $table) {
                if ($this->hasIndex('change_requests', 'change_requests_affected_type_affected_id_index')) {
                    $table->dropIndex(['affected_type', 'affected_id']);
                }
                $table->dropColumn(['affected_type', 'affected_id']);
            });
        }

        Schema::table('change_requests', function (Blueprint $table) {
            if (! $this->hasIndex('change_requests', 'change_requests_project_id_business_need_id_index')
                && Schema::hasColumn('change_requests', 'business_need_id')) {
                $table->index(['project_id', 'business_need_id']);
            }
            if (! $this->hasIndex('change_requests', 'change_requests_project_id_stakeholder_need_id_index')
                && Schema::hasColumn('change_requests', 'stakeholder_need_id')) {
                $table->index(['project_id', 'stakeholder_need_id']);
            }
        });
    }

    private function recoverFailedSqliteRebuild(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        if (! Schema::hasTable('change_requests_legacy_tmp')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            Schema::dropIfExists('change_requests');
            Schema::rename('change_requests_legacy_tmp', 'change_requests');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function backfillAffectedSubject(): void
    {
        $select = ['id'];
        foreach ($this->legacyColumns as $column) {
            if (Schema::hasColumn('change_requests', $column)) {
                $select[] = $column;
            }
        }

        if (count($select) === 1) {
            return;
        }

        $rows = DB::table('change_requests')->select($select)->get();

        foreach ($rows as $row) {
            $subject = match (true) {
                isset($row->business_objective_id) && $row->business_objective_id !== null
                    => ['business_objective', (int) $row->business_objective_id],
                isset($row->business_need_id) && $row->business_need_id !== null
                    => ['business_need', (int) $row->business_need_id],
                isset($row->stakeholder_need_id) && $row->stakeholder_need_id !== null
                    => ['stakeholder_need', (int) $row->stakeholder_need_id],
                isset($row->feature_id) && $row->feature_id !== null
                    => ['feature', (int) $row->feature_id],
                isset($row->functional_requirement_id) && $row->functional_requirement_id !== null
                    => ['functional_requirement', (int) $row->functional_requirement_id],
                default => null,
            };

            if ($subject === null) {
                continue;
            }

            DB::table('change_requests')->where('id', $row->id)->update([
                'affected_type' => $subject[0],
                'affected_id' => $subject[1],
            ]);
        }
    }

    private function dropLegacyIndexes(): void
    {
        foreach ([
            'change_requests_project_id_business_need_id_index',
            'change_requests_project_id_stakeholder_need_id_index',
        ] as $index) {
            if (! $this->hasIndex('change_requests', $index)) {
                continue;
            }

            Schema::table('change_requests', function (Blueprint $table) use ($index) {
                $table->dropIndex($index);
            });
        }
    }

    private function dropLegacyColumns(): void
    {
        $toDrop = array_values(array_filter(
            $this->legacyColumns,
            fn (string $column): bool => Schema::hasColumn('change_requests', $column)
        ));

        if ($toDrop === []) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteWithoutLegacyColumns($toDrop);

            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            Schema::table('change_requests', function (Blueprint $table) use ($toDrop) {
                $table->dropColumn($toDrop);
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * @param  list<string>  $toDrop
     */
    private function rebuildSqliteWithoutLegacyColumns(array $toDrop): void
    {
        $keep = array_values(array_diff(
            Schema::getColumnListing('change_requests'),
            $toDrop
        ));

        $quoted = implode(', ', array_map(
            fn (string $column): string => '"'.$column.'"',
            $keep
        ));

        Schema::disableForeignKeyConstraints();

        try {
            Schema::rename('change_requests', 'change_requests_legacy_tmp');

            // Renamed SQLite tables keep their index names globally; free them before recreate.
            foreach (DB::select("PRAGMA index_list('change_requests_legacy_tmp')") as $index) {
                $name = (string) ($index->name ?? '');
                if ($name === '' || str_starts_with($name, 'sqlite_autoindex_')) {
                    continue;
                }
                DB::statement('DROP INDEX IF EXISTS "'.$name.'"');
            }

            Schema::create('change_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('number')->nullable();
                $table->string('title');
                $table->foreignId('project_id')->constrained();
                $table->text('problem');
                $table->text('proposed_change');
                $table->string('requestor')->nullable();
                $table->string('impact_level')->default('medium');
                $table->text('impact_notes')->nullable();
                $table->string('affected_type')->nullable();
                $table->unsignedBigInteger('affected_id')->nullable();
                $table->foreignId('priority_id')->nullable()->constrained('priorities');
                $table->string('status')->default('draft');
                $table->timestamps();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->softDeletes();

                $table->unique(['project_id', 'number'], 'change_requests_project_id_number_unique');
                $table->index(['project_id', 'status']);
                $table->index(['affected_type', 'affected_id']);
            });

            DB::statement(
                "INSERT INTO change_requests ({$quoted}) SELECT {$quoted} FROM change_requests_legacy_tmp"
            );

            Schema::drop('change_requests_legacy_tmp');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            foreach (DB::select("PRAGMA index_list('{$table}')") as $index) {
                if (($index->name ?? null) === $name) {
                    return true;
                }
            }

            return false;
        }

        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => ($index['name'] ?? null) === $name);
    }
};
