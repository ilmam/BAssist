<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('briefs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('workspace_id')->constrained();
            $table->text('ask')->nullable();
            $table->string('source_type')->nullable();
            $table->string('source_note')->nullable();
            $table->text('stated_constraints')->nullable();
            $table->text('stated_success_measures')->nullable();
            $table->text('open_questions')->nullable();
            $table->string('status')->default('received');
            $table->text('status_reason')->nullable();
            $table->date('received_at')->nullable();
            // A re-brief is a new row pointing at the version it replaces; earlier versions stay as received.
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('previous_version_id')->nullable()->constrained('briefs')->nullOnDelete();
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->index(['workspace_id', 'status']);
        });

        // Which project takes which part of the ask. No rows = no brief (a normal start).
        Schema::create('brief_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brief_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->text('scope_note')->nullable();
            $table->timestamps();

            $table->unique(['brief_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brief_project');
        Schema::dropIfExists('briefs');
    }
};
