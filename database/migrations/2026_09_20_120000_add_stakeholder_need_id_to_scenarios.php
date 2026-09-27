<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenarios', function (Blueprint $table) {
            $table->foreignId('stakeholder_need_id')
                ->nullable()
                ->after('feature_id')
                ->constrained('stakeholder_needs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stakeholder_need_id');
        });
    }
};
