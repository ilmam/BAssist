<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('architectures', function (Blueprint $table) {
            $table->json('layout')->nullable()->after('relationships');
        });
    }

    public function down(): void
    {
        Schema::table('architectures', function (Blueprint $table) {
            $table->dropColumn('layout');
        });
    }
};
