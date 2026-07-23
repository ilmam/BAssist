<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('state_flows', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('project_id')->constrained();
            $table->text('description')->nullable();
            $table->json('transitions')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('statuses');
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('state_flows');
    }
};
