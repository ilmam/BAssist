<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An answered thread normally ends with a change to the specification. When the answer needed
 * none, the reporter says why; that reason is kept so the thread is not flagged as a decision
 * living only in a comment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->text('no_spec_change_reason')->nullable()->after('implemented_by');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('no_spec_change_reason');
        });
    }
};
