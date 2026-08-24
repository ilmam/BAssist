<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_dictionaries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('project_id')->constrained();
            $table->text('description')->nullable();
            $table->json('entities')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('statuses');
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });

        $templates = DB::table('role_entity_permissions')
            ->where('entity', 'StateFlow')
            ->get();

        foreach ($templates as $template) {
            DB::table('role_entity_permissions')->updateOrInsert(
                [
                    'role_id' => $template->role_id,
                    'entity' => 'DataDictionary',
                ],
                [
                    'can_view' => $template->can_view,
                    'can_create' => $template->can_create,
                    'can_update' => $template->can_update,
                    'can_delete' => $template->can_delete,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('role_entity_permissions')->where('entity', 'DataDictionary')->delete();
        Schema::dropIfExists('data_dictionaries');
    }
};
