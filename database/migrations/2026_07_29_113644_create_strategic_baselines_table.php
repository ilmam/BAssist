<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategic_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            $table->text('current_state')->nullable();
            $table->text('future_state')->nullable();
            $table->text('change_strategy')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->unique('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategic_baselines');
    }
};
