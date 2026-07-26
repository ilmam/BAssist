<?php

use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\StakeholderNeed;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    private array $models = [
        BusinessObjective::class,
        BusinessNeed::class,
        StakeholderNeed::class,
    ];

    public function up(): void
    {
        foreach ($this->models as $modelClass) {
            $tableName = (new $modelClass)->getTable();

            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedInteger('number')->nullable()->after('id');
            });
        }

        foreach ($this->models as $modelClass) {
            $modelClass::withTrashed()
                ->orderBy('id')
                ->get(['id', 'project_id', 'number'])
                ->groupBy('project_id')
                ->each(function ($rows): void {
                    $n = 1;
                    foreach ($rows as $row) {
                        $row->forceFill(['number' => $n++])->saveQuietly();
                    }
                });
        }

        foreach ($this->models as $modelClass) {
            $tableName = (new $modelClass)->getTable();

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->unique(['project_id', 'number'], $tableName.'_project_id_number_unique');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->models as $modelClass) {
            $tableName = (new $modelClass)->getTable();

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropUnique($tableName.'_project_id_number_unique');
                $table->dropColumn('number');
            });
        }
    }
};
