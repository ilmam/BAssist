<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stakeholders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('project_id')->constrained();
            $table->string('type')->nullable();
            $table->string('influence')->nullable();
            $table->string('interest')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_system')->default(false);
            $table->string('system_key')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('statuses');
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->unique(['project_id', 'system_key'], 'stakeholders_project_system_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stakeholders');
    }
};
