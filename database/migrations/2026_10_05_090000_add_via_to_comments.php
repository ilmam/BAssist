<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which channel a comment came through: null = web UI, "api" = personal API
 * token, "mcp" = AI assistant (see App\Support\RequestChannel). Same meaning as
 * activity_log.via.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->string('via', 20)->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('via');
        });
    }
};
