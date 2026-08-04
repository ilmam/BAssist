<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->string('requestor')->nullable()->after('proposed_change');
            $table->string('impact_level')->default('medium')->after('requestor');
            $table->foreignId('business_objective_id')
                ->nullable()
                ->constrained('business_objectives')
                ->nullOnDelete();
            $table->foreignId('feature_id')
                ->nullable()
                ->constrained('features')
                ->nullOnDelete();
            $table->foreignId('functional_requirement_id')
                ->nullable()
                ->constrained('functional_requirements')
                ->nullOnDelete();
        });

        DB::table('change_requests')
            ->where('status', 'submitted')
            ->update(['status' => 'under_review']);
    }

    public function down(): void
    {
        DB::table('change_requests')
            ->where('status', 'under_review')
            ->update(['status' => 'submitted']);

        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('functional_requirement_id');
            $table->dropConstrainedForeignId('feature_id');
            $table->dropConstrainedForeignId('business_objective_id');
            $table->dropColumn(['requestor', 'impact_level']);
        });
    }
};
