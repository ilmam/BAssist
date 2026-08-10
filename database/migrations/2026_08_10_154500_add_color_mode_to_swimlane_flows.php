<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('swimlane_flows', function (Blueprint $table) {
            $table->string('color_mode', 16)->default('both')->after('direction');
        });
    }

    public function down(): void
    {
        Schema::table('swimlane_flows', function (Blueprint $table) {
            $table->dropColumn('color_mode');
        });
    }
};
