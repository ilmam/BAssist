<?php

use App\Support\EntityPriority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remap High/Medium/Low priority master rows to MoSCoW and add Won't.
 * Preserves existing priority_id foreign keys by updating rows in place.
 */
return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'high' => [
                'code' => EntityPriority::MUST,
                'name' => 'Must',
                'sort_order' => 10,
                'description' => 'Without this, the release fails.',
            ],
            'medium' => [
                'code' => EntityPriority::SHOULD,
                'name' => 'Should',
                'sort_order' => 20,
                'description' => 'Important, but a workaround exists.',
            ],
            'low' => [
                'code' => EntityPriority::COULD,
                'name' => 'Could',
                'sort_order' => 30,
                'description' => 'Nice to have if capacity remains.',
            ],
        ];

        foreach ($map as $oldCode => $attrs) {
            DB::table('priorities')
                ->where('code', $oldCode)
                ->update([
                    'code' => $attrs['code'],
                    'name' => $attrs['name'],
                    'sort_order' => $attrs['sort_order'],
                    'description' => $attrs['description'],
                    'is_system' => true,
                    'updated_at' => now(),
                ]);
        }

        $existsWont = DB::table('priorities')->where('code', EntityPriority::WONT)->exists();
        if (! $existsWont) {
            DB::table('priorities')->insert([
                'code' => EntityPriority::WONT,
                'name' => "Won't",
                'sort_order' => 40,
                'description' => 'Explicitly out of scope for this release.',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        EntityPriority::forgetCache();
    }

    public function down(): void
    {
        $map = [
            EntityPriority::MUST => [
                'code' => 'high',
                'name' => 'High',
                'sort_order' => 10,
                'description' => 'Must be addressed soon.',
            ],
            EntityPriority::SHOULD => [
                'code' => 'medium',
                'name' => 'Medium',
                'sort_order' => 20,
                'description' => 'Important but not urgent.',
            ],
            EntityPriority::COULD => [
                'code' => 'low',
                'name' => 'Low',
                'sort_order' => 30,
                'description' => 'Can wait relative to other work.',
            ],
        ];

        foreach ($map as $oldCode => $attrs) {
            DB::table('priorities')
                ->where('code', $oldCode)
                ->update([
                    'code' => $attrs['code'],
                    'name' => $attrs['name'],
                    'sort_order' => $attrs['sort_order'],
                    'description' => $attrs['description'],
                    'updated_at' => now(),
                ]);
        }

        DB::table('priorities')->where('code', EntityPriority::WONT)->delete();

        EntityPriority::forgetCache();
    }
};
