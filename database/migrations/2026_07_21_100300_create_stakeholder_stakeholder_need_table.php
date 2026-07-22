<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stakeholder_stakeholder_need', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stakeholder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stakeholder_need_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['stakeholder_id', 'stakeholder_need_id'], 'stakeholder_stakeholder_need_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stakeholder_stakeholder_need');
    }
};
