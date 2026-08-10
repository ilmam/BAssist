<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_objective_stakeholder_need', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_objective_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stakeholder_need_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['business_objective_id', 'stakeholder_need_id'],
                'objective_stakeholder_need_unique'
            );
        });

        if (Schema::hasTable('business_need_stakeholder_need')) {
            $pairs = DB::table('business_need_stakeholder_need')->get();
            $now = now();

            foreach ($pairs as $pair) {
                $objectiveId = DB::table('business_need_business_objective')
                    ->where('business_need_id', $pair->business_need_id)
                    ->orderByDesc('is_primary')
                    ->orderBy('business_objective_id')
                    ->value('business_objective_id');

                if ($objectiveId === null) {
                    continue;
                }

                $exists = DB::table('business_objective_stakeholder_need')
                    ->where('business_objective_id', $objectiveId)
                    ->where('stakeholder_need_id', $pair->stakeholder_need_id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('business_objective_stakeholder_need')->insert([
                    'business_objective_id' => $objectiveId,
                    'stakeholder_need_id' => $pair->stakeholder_need_id,
                    'created_at' => $pair->created_at ?? $now,
                    'updated_at' => $pair->updated_at ?? $now,
                ]);
            }

            Schema::dropIfExists('business_need_stakeholder_need');
        }
    }

    public function down(): void
    {
        Schema::create('business_need_stakeholder_need', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_need_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stakeholder_need_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['business_need_id', 'stakeholder_need_id'], 'need_stakeholder_need_unique');
        });

        if (Schema::hasTable('business_objective_stakeholder_need')) {
            $pairs = DB::table('business_objective_stakeholder_need')->get();
            $now = now();

            foreach ($pairs as $pair) {
                $needId = DB::table('business_need_business_objective')
                    ->where('business_objective_id', $pair->business_objective_id)
                    ->orderByDesc('is_primary')
                    ->orderBy('business_need_id')
                    ->value('business_need_id');

                if ($needId === null) {
                    continue;
                }

                $exists = DB::table('business_need_stakeholder_need')
                    ->where('business_need_id', $needId)
                    ->where('stakeholder_need_id', $pair->stakeholder_need_id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('business_need_stakeholder_need')->insert([
                    'business_need_id' => $needId,
                    'stakeholder_need_id' => $pair->stakeholder_need_id,
                    'created_at' => $pair->created_at ?? $now,
                    'updated_at' => $pair->updated_at ?? $now,
                ]);
            }

            Schema::dropIfExists('business_objective_stakeholder_need');
        }
    }
};
