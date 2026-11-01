<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * An assistant reply on an answered thread now puts it back to open (a follow-up
 * question or clarification waits for a person). Threads answered before that rule
 * whose latest reply is the assistant's are in the same position: reopen them.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('comments')
            ->whereNull('parent_id')
            ->whereNull('deleted_at')
            ->whereNull('resolved_at')
            ->where('status', 'answered')
            ->whereRaw("(select r.via from comments as r where r.parent_id = comments.id and r.deleted_at is null order by r.id desc limit 1) = 'mcp'")
            ->update(['status' => 'open']);
    }

    public function down(): void
    {
        // Not reversible: the earlier status was not recorded.
    }
};
