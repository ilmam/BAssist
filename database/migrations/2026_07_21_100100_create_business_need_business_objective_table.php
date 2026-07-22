<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_need_business_objective', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_need_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_objective_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['business_need_id', 'business_objective_id'], 'need_objective_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_need_business_objective');
    }
};
