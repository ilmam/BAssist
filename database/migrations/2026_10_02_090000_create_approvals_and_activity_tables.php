<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Separate "approve" permission per role and entity (BABOK 5.5 Approve Requirements).
        Schema::table('role_entity_permissions', function (Blueprint $table) {
            $table->boolean('can_approve')->default(false)->after('can_delete');
        });

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision'); // approved | changes_requested
            $table->text('note')->nullable();
            // Set when a later meaningful edit means the decision no longer covers the current content.
            $table->timestamp('invalidated_at')->nullable();
            $table->string('invalidated_reason')->nullable();
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id']);
            $table->index(['project_id', 'invalidated_at']);
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event'); // created | updated | deleted | approved | changes_requested | approval_reset
            $table->json('changes')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('approvals');
        Schema::table('role_entity_permissions', function (Blueprint $table) {
            $table->dropColumn('can_approve');
        });
    }
};
