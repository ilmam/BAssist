<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('change_requests', 'stakeholder_need_id')) {
            Schema::table('change_requests', function (Blueprint $table) {
                $table->foreignId('stakeholder_need_id')
                    ->nullable()
                    ->after('impact_notes')
                    ->constrained('stakeholder_needs')
                    ->nullOnDelete();
                $table->index(['project_id', 'stakeholder_need_id']);
            });
        }

        if (Schema::hasColumn('change_requests', 'affected_type')) {
            $rows = DB::table('change_requests')
                ->select(['id', 'affected_type', 'affected_id', 'project_id'])
                ->whereNotNull('affected_type')
                ->whereNotNull('affected_id')
                ->get();

            foreach ($rows as $row) {
                $snId = $this->resolveStakeholderNeedId(
                    (string) $row->affected_type,
                    (int) $row->affected_id,
                    (int) $row->project_id,
                );

                if ($snId === null) {
                    continue;
                }

                DB::table('change_requests')->where('id', $row->id)->update([
                    'stakeholder_need_id' => $snId,
                ]);
            }

            $this->dropAffectedColumns();
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('change_requests', 'affected_type')) {
            Schema::table('change_requests', function (Blueprint $table) {
                $table->string('affected_type')->nullable()->after('impact_notes');
                $table->unsignedBigInteger('affected_id')->nullable()->after('affected_type');
                $table->index(['affected_type', 'affected_id']);
            });
        }

        if (Schema::hasColumn('change_requests', 'stakeholder_need_id')) {
            $rows = DB::table('change_requests')
                ->select(['id', 'stakeholder_need_id'])
                ->whereNotNull('stakeholder_need_id')
                ->get();

            foreach ($rows as $row) {
                DB::table('change_requests')->where('id', $row->id)->update([
                    'affected_type' => 'stakeholder_need',
                    'affected_id' => $row->stakeholder_need_id,
                ]);
            }

            Schema::table('change_requests', function (Blueprint $table) {
                if ($this->hasIndex('change_requests', 'change_requests_project_id_stakeholder_need_id_index')) {
                    $table->dropIndex(['project_id', 'stakeholder_need_id']);
                }
                $table->dropConstrainedForeignId('stakeholder_need_id');
            });
        }
    }

    private function resolveStakeholderNeedId(string $type, int $id, int $projectId): ?int
    {
        return match ($type) {
            'stakeholder_need' => DB::table('stakeholder_needs')->where('id', $id)->value('id'),
            'business_need' => DB::table('business_need_stakeholder_need')
                ->join('stakeholder_needs', 'stakeholder_needs.id', '=', 'business_need_stakeholder_need.stakeholder_need_id')
                ->where('business_need_stakeholder_need.business_need_id', $id)
                ->where('stakeholder_needs.project_id', $projectId)
                ->orderBy('stakeholder_needs.id')
                ->value('stakeholder_needs.id'),
            'feature' => DB::table('features')->where('id', $id)->value('stakeholder_need_id'),
            'functional_requirement' => DB::table('functional_requirements')->where('id', $id)->value('stakeholder_need_id'),
            'business_objective' => DB::table('stakeholder_needs')
                ->join('business_need_stakeholder_need', 'business_need_stakeholder_need.stakeholder_need_id', '=', 'stakeholder_needs.id')
                ->join('business_need_business_objective', 'business_need_business_objective.business_need_id', '=', 'business_need_stakeholder_need.business_need_id')
                ->where('business_need_business_objective.business_objective_id', $id)
                ->where('stakeholder_needs.project_id', $projectId)
                ->orderBy('stakeholder_needs.id')
                ->value('stakeholder_needs.id'),
            default => null,
        };
    }

    private function dropAffectedColumns(): void
    {
        if (! Schema::hasColumn('change_requests', 'affected_type')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::disableForeignKeyConstraints();
            try {
                Schema::table('change_requests', function (Blueprint $table) {
                    if ($this->hasIndex('change_requests', 'change_requests_affected_type_affected_id_index')) {
                        $table->dropIndex(['affected_type', 'affected_id']);
                    }
                });
                Schema::table('change_requests', function (Blueprint $table) {
                    $table->dropColumn(['affected_type', 'affected_id']);
                });
            } finally {
                Schema::enableForeignKeyConstraints();
            }

            return;
        }

        Schema::table('change_requests', function (Blueprint $table) {
            if ($this->hasIndex('change_requests', 'change_requests_affected_type_affected_id_index')) {
                $table->dropIndex(['affected_type', 'affected_id']);
            }
            $table->dropColumn(['affected_type', 'affected_id']);
        });
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
