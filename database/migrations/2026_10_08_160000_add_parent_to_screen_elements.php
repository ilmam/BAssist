<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Screen elements nest: a row may sit inside a container row (panel, columns,
 * column, table). See docs/design-layer.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screen_elements', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('screen_id')
                ->constrained('screen_elements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('screen_elements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
