<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->nullable();
            $table->string('title');
            $table->foreignId('project_id')->constrained();
            $table->text('problem');
            $table->text('proposed_change');
            $table->foreignId('business_need_id')
                ->nullable()
                ->constrained('business_needs')
                ->nullOnDelete();
            $table->foreignId('stakeholder_need_id')
                ->nullable()
                ->constrained('stakeholder_needs')
                ->nullOnDelete();
            $table->text('impact_notes')->nullable();
            $table->foreignId('priority_id')->nullable()->constrained('priorities');
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->unique(['project_id', 'number'], 'change_requests_project_id_number_unique');
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'business_need_id']);
            $table->index(['project_id', 'stakeholder_need_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_requests');
    }
};
