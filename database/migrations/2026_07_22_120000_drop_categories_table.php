<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('categories');
    }

    public function down(): void
    {
        // Intentionally empty: Category was a throwaway test entity.
    }
};
