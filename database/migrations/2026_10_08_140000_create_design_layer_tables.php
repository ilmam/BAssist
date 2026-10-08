<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Design layer PoC (docs/design-layer.md): screens, their element rows, and the
 * upstream link from a screen to the functional requirement(s) it realizes.
 */
return new class extends Migration
{
    private const ENTITIES = ['Screen', 'ScreenElement'];

    public function up(): void
    {
        Schema::create('screens', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('project_id')->constrained();
            $table->text('description')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('statuses');
            $this->audit($table);
        });

        Schema::create('screen_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained();
            $table->unsignedInteger('position')->default(0);
            // Placement hint: elements sharing a row value sit on one line.
            $table->unsignedInteger('row')->nullable();
            $table->string('kind');
            $table->string('label');
            // Exception only: an element that maps to its own requirement.
            $table->foreignId('functional_requirement_id')->nullable()->constrained()->nullOnDelete();
            $this->audit($table);
        });

        Schema::create('functional_requirement_screen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('functional_requirement_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['screen_id', 'functional_requirement_id'], 'screen_fr_unique');
        });

        $templates = DB::table('role_entity_permissions')->where('entity', 'DataDictionary')->get();

        foreach ($templates as $template) {
            foreach (self::ENTITIES as $entity) {
                DB::table('role_entity_permissions')->updateOrInsert(
                    ['role_id' => $template->role_id, 'entity' => $entity],
                    [
                        'can_view' => $template->can_view,
                        'can_create' => $template->can_create,
                        'can_update' => $template->can_update,
                        'can_delete' => $template->can_delete,
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('role_entity_permissions')->whereIn('entity', self::ENTITIES)->delete();
        Schema::dropIfExists('functional_requirement_screen');
        Schema::dropIfExists('screen_elements');
        Schema::dropIfExists('screens');
    }

    private function audit(Blueprint $table): void
    {
        $table->timestamps();
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        $table->softDeletes();
    }
};
