<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_needs', function (Blueprint $table) {
            if (! Schema::hasColumn('business_needs', 'need_type')) {
                $table->string('need_type')->nullable()->after('title');
            }
            if (! Schema::hasColumn('business_needs', 'impact')) {
                $table->text('impact')->nullable()->after('rationale');
            }
            if (! Schema::hasColumn('business_needs', 'do_nothing_consequence')) {
                $table->text('do_nothing_consequence')->nullable()->after('impact');
            }
        });

        Schema::table('business_objectives', function (Blueprint $table) {
            if (! Schema::hasColumn('business_objectives', 'potential_value')) {
                $table->text('potential_value')->nullable()->after('success_measure');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_needs', function (Blueprint $table) {
            foreach (['need_type', 'impact', 'do_nothing_consequence'] as $column) {
                if (Schema::hasColumn('business_needs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('business_objectives', function (Blueprint $table) {
            if (Schema::hasColumn('business_objectives', 'potential_value')) {
                $table->dropColumn('potential_value');
            }
        });
    }
};
