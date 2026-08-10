<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('non_functional_requirements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->nullable();
            $table->string('title');
            $table->foreignId('project_id')->constrained();
            $table->foreignId('stakeholder_need_id')
                ->nullable()
                ->constrained('stakeholder_needs')
                ->nullOnDelete();
            $table->foreignId('change_request_id')
                ->nullable()
                ->constrained('change_requests')
                ->nullOnDelete();
            $table->string('category', 64);
            $table->text('description');
            $table->text('acceptance_criteria')->nullable();
            $table->foreignId('priority_id')->nullable()->constrained('priorities');
            $table->foreignId('status_id')->nullable()->constrained('statuses');
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->unique(['project_id', 'number'], 'non_functional_requirements_project_id_number_unique');
            $table->index(['project_id', 'stakeholder_need_id']);
            $table->index(['project_id', 'status_id']);
            $table->index(['project_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('non_functional_requirements');
    }
};
