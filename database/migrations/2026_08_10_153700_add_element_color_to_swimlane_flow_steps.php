<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('swimlane_flow_steps', function (Blueprint $table) {
            $table->string('element_color', 32)->nullable()->after('lane_color');
        });
    }

    public function down(): void
    {
        Schema::table('swimlane_flow_steps', function (Blueprint $table) {
            $table->dropColumn('element_color');
        });
    }
};
