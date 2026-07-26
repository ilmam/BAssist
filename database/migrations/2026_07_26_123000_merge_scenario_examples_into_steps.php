<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('scenarios') || ! Schema::hasColumn('scenarios', 'examples')) {
            return;
        }

        DB::table('scenarios')
            ->select(['id', 'steps', 'examples'])
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $steps = trim((string) ($row->steps ?? ''));
                    $examples = trim((string) ($row->examples ?? ''));

                    if ($examples === '') {
                        continue;
                    }

                    if (preg_match('/^\s*Examples\s*:/i', $examples) !== 1) {
                        $examples = "Examples:\n".$examples;
                    }

                    $merged = $steps === ''
                        ? $examples
                        : $steps."\n\n".$examples;

                    DB::table('scenarios')
                        ->where('id', $row->id)
                        ->update(['steps' => $merged]);
                }
            });

        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropColumn('examples');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('scenarios') || Schema::hasColumn('scenarios', 'examples')) {
            return;
        }

        Schema::table('scenarios', function (Blueprint $table) {
            $table->text('examples')->nullable()->after('steps');
        });
    }
};
