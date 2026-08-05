<?php

use App\Support\ProcessStepSatisfyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Promote BPD elements JSON to swimlane_flow_steps rows (source of truth).
 * Migrates legacy satisfy_type/satisfy_id into FR/Feature.swimlane_flow_step_id + step.stakeholder_need_id.
 * Clears swimlane_flows.elements after copy (column kept nullable for rollback safety).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swimlane_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->nullable();
            $table->foreignId('swimlane_flow_id')->constrained('swimlane_flows')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects');
            $table->unsignedInteger('position')->default(0);
            $table->string('lane');
            $table->string('from_label')->nullable();
            $table->string('type');
            $table->string('label');
            $table->string('line_title')->nullable();
            $table->foreignId('stakeholder_need_id')
                ->nullable()
                ->constrained('stakeholder_needs')
                ->nullOnDelete();
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->unique(['project_id', 'number'], 'swimlane_flow_steps_project_id_number_unique');
            $table->index(['swimlane_flow_id', 'position']);
            $table->index(['project_id', 'type']);
            $table->index(['stakeholder_need_id']);
        });

        Schema::table('features', function (Blueprint $table) {
            $table->foreignId('swimlane_flow_step_id')
                ->nullable()
                ->constrained('swimlane_flow_steps')
                ->nullOnDelete();
        });

        Schema::table('functional_requirements', function (Blueprint $table) {
            $table->foreignId('swimlane_flow_step_id')
                ->nullable()
                ->constrained('swimlane_flow_steps')
                ->nullOnDelete();
        });

        $this->migrateElementsToRows();

        DB::table('swimlane_flows')->update(['elements' => null]);
    }

    public function down(): void
    {
        Schema::table('functional_requirements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('swimlane_flow_step_id');
        });

        Schema::table('features', function (Blueprint $table) {
            $table->dropConstrainedForeignId('swimlane_flow_step_id');
        });

        Schema::dropIfExists('swimlane_flow_steps');
    }

    protected function migrateElementsToRows(): void
    {
        if (! Schema::hasTable('swimlane_flows')) {
            return;
        }

        $nextNumberByProject = [];
        $usedNumbersByProject = [];
        $types = ['start', 'process', 'decision', 'end'];
        $satisfiable = ['process', 'decision'];

        $flows = DB::table('swimlane_flows')->whereNull('deleted_at')->orderBy('id')->get();

        foreach ($flows as $flow) {
            $projectId = (int) $flow->project_id;
            $elements = json_decode((string) ($flow->elements ?? '[]'), true);
            if (! is_array($elements)) {
                $elements = [];
            }

            $rows = $this->normalizeLegacyElements($elements, $types, $satisfiable);
            $now = now();

            foreach ($rows as $index => $row) {
                $number = $this->allocateNumber(
                    $projectId,
                    $row['code'],
                    $nextNumberByProject,
                    $usedNumbersByProject,
                );

                $stepId = DB::table('swimlane_flow_steps')->insertGetId([
                    'number' => $number,
                    'swimlane_flow_id' => $flow->id,
                    'project_id' => $projectId,
                    'position' => $index,
                    'lane' => $row['lane'],
                    'from_label' => $row['from'],
                    'type' => $row['type'],
                    'label' => $row['label'],
                    'line_title' => $row['line_title'],
                    'stakeholder_need_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'created_by' => $flow->created_by,
                    'updated_by' => $flow->updated_by,
                ]);

                $this->relinkSatisfyTarget(
                    $stepId,
                    $projectId,
                    $row['satisfy_type'],
                    $row['satisfy_id'],
                    $now,
                );
            }
        }
    }

    /**
     * @param  list<mixed>  $elements
     * @param  list<string>  $types
     * @param  list<string>  $satisfiable
     * @return list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null, code: string|null, satisfy_type: string|null, satisfy_id: int|null}>
     */
    protected function normalizeLegacyElements(array $elements, array $types, array $satisfiable): array
    {
        $rows = [];

        foreach ($elements as $row) {
            if (! is_array($row)) {
                continue;
            }

            $lane = trim((string) ($row['lane'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            $type = strtolower(trim((string) ($row['type'] ?? '')));
            $from = trim((string) ($row['from'] ?? ''));
            $lineTitle = trim((string) ($row['line_title'] ?? ''));
            $code = $this->normalizeCode($row['code'] ?? null);
            [$satisfyType, $satisfyId] = $this->normalizeSatisfy($row);

            if ($lane === '' || $label === '' || ! in_array($type, $types, true)) {
                continue;
            }

            if (! in_array($type, $satisfiable, true)) {
                $satisfyType = null;
                $satisfyId = null;
            }

            $rows[] = [
                'lane' => $lane,
                'from' => $from !== '' ? $from : null,
                'type' => $type,
                'label' => $label,
                'line_title' => $lineTitle !== '' ? $lineTitle : null,
                'code' => $code,
                'satisfy_type' => $satisfyType,
                'satisfy_id' => $satisfyId,
            ];
        }

        return $this->assignMissingCodes($rows);
    }

    /**
     * @param  list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null, code: string|null, satisfy_type: string|null, satisfy_id: int|null}>  $rows
     * @return list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null, code: string|null, satisfy_type: string|null, satisfy_id: int|null}>
     */
    protected function assignMissingCodes(array $rows): array
    {
        $max = 0;
        foreach ($rows as $row) {
            if (preg_match('/^PS-(\d+)$/i', (string) ($row['code'] ?? ''), $matches) === 1) {
                $max = max($max, (int) $matches[1]);
            }
        }

        foreach ($rows as $index => $row) {
            if (($row['code'] ?? null) !== null && $row['code'] !== '') {
                continue;
            }
            $max++;
            $rows[$index]['code'] = 'PS-'.$max;
        }

        return $rows;
    }

    protected function normalizeCode(mixed $code): ?string
    {
        $code = trim((string) ($code ?? ''));
        if ($code === '') {
            return null;
        }

        if (preg_match('/^PS-(\d+)$/i', $code, $matches) === 1) {
            return 'PS-'.(int) $matches[1];
        }

        return $code;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{0: string|null, 1: int|null}
     */
    protected function normalizeSatisfy(array $row): array
    {
        if (array_key_exists('satisfy', $row) && (string) ($row['satisfy'] ?? '') !== '') {
            $decoded = ProcessStepSatisfyType::decode($row['satisfy']);

            return [$decoded['type'], $decoded['id']];
        }

        $type = isset($row['satisfy_type']) ? trim((string) $row['satisfy_type']) : '';
        $id = isset($row['satisfy_id']) && is_numeric($row['satisfy_id']) ? (int) $row['satisfy_id'] : 0;

        if (! ProcessStepSatisfyType::isValid($type) || $id < 1) {
            return [null, null];
        }

        return [$type, $id];
    }

    protected function relinkSatisfyTarget(
        int $stepId,
        int $projectId,
        ?string $satisfyType,
        ?int $satisfyId,
        mixed $now,
    ): void {
        if (! ProcessStepSatisfyType::isValid($satisfyType) || $satisfyId === null || $satisfyId < 1) {
            return;
        }

        $table = match ($satisfyType) {
            ProcessStepSatisfyType::FEATURE => 'features',
            ProcessStepSatisfyType::FUNCTIONAL_REQUIREMENT => 'functional_requirements',
            default => null,
        };

        if ($table === null || ! Schema::hasTable($table)) {
            return;
        }

        $target = DB::table($table)
            ->where('id', $satisfyId)
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->first();

        if ($target === null) {
            return;
        }

        // First step wins when multiple steps pointed at the same requirement.
        if ($target->swimlane_flow_step_id === null) {
            DB::table($table)->where('id', $target->id)->update([
                'swimlane_flow_step_id' => $stepId,
                'updated_at' => $now,
            ]);
        }

        if (! empty($target->stakeholder_need_id)) {
            DB::table('swimlane_flow_steps')->where('id', $stepId)->update([
                'stakeholder_need_id' => (int) $target->stakeholder_need_id,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $nextNumberByProject
     * @param  array<int, array<int, true>>  $usedNumbersByProject
     */
    protected function allocateNumber(
        int $projectId,
        mixed $code,
        array &$nextNumberByProject,
        array &$usedNumbersByProject,
    ): int {
        $usedNumbersByProject[$projectId] ??= [];
        $nextNumberByProject[$projectId] ??= 1;

        $preferred = null;
        $code = trim((string) ($code ?? ''));
        if (preg_match('/^PS-(\d+)$/i', $code, $matches) === 1) {
            $preferred = (int) $matches[1];
        }

        if ($preferred !== null && $preferred > 0 && ! isset($usedNumbersByProject[$projectId][$preferred])) {
            $usedNumbersByProject[$projectId][$preferred] = true;
            if ($preferred >= $nextNumberByProject[$projectId]) {
                $nextNumberByProject[$projectId] = $preferred + 1;
            }

            return $preferred;
        }

        while (isset($usedNumbersByProject[$projectId][$nextNumberByProject[$projectId]])) {
            $nextNumberByProject[$projectId]++;
        }

        $number = $nextNumberByProject[$projectId];
        $usedNumbersByProject[$projectId][$number] = true;
        $nextNumberByProject[$projectId] = $number + 1;

        return $number;
    }
};
