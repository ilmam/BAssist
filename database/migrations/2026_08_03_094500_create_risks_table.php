<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->nullable();
            $table->string('title');
            $table->foreignId('project_id')->constrained();
            $table->text('description')->nullable();
            $table->string('category')->default('technical');
            $table->string('likelihood')->default('medium');
            $table->string('impact')->default('medium');
            $table->string('response')->nullable();
            $table->text('treatment')->nullable();
            $table->string('trigger')->nullable();
            $table->string('owner')->nullable();
            $table->string('status')->default('open');
            $table->string('source')->nullable();
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->unique(['project_id', 'number'], 'risks_project_id_number_unique');
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risks');
    }
};
