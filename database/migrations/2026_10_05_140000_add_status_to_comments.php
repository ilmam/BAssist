<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Comment threads get a status (see App\Support\CommentStatus) so a reply, an
 * implemented decision and a verified close are three different things.
 * Existing threads: resolved → closed; with a reply → answered; otherwise open.
 * Replies (parent_id set) carry no status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->string('status', 20)->nullable()->after('via');
            $table->timestamp('implemented_at')->nullable()->after('status');
            $table->foreignId('implemented_by')->nullable()->after('implemented_at')->constrained('users')->nullOnDelete();
            $table->index(['project_id', 'status']);
        });

        DB::table('comments')->whereNull('parent_id')->whereNotNull('resolved_at')->update(['status' => 'closed']);
        DB::table('comments')->whereNull('parent_id')->whereNull('resolved_at')->update(['status' => 'open']);
        DB::table('comments')
            ->whereNull('parent_id')
            ->whereNull('resolved_at')
            ->whereIn('id', DB::table('comments')->select('parent_id')->whereNotNull('parent_id')->whereNull('deleted_at'))
            ->update(['status' => 'answered']);
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'status']);
            $table->dropConstrainedForeignId('implemented_by');
            $table->dropColumn(['status', 'implemented_at']);
        });
    }
};
