<?php

namespace App\Repositories;

use App\Data\SwimlaneFlowData;
use App\Data\SwimlaneFlowViewData;
use App\Models\SwimlaneFlow;
use App\Models\SwimlaneFlowStep;
use App\Services\SwimlaneMermaidGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SwimlaneFlowRepository extends BaseRepository
{
    public Model $model;

    public $editDto = SwimlaneFlowData::class;

    public $viewDto = SwimlaneFlowViewData::class;

    protected array $listFilters = [
        'project_id',
        'status_id',
    ];

    protected array $listContextFilters = [
        'workspace_id' => ['project', 'workspace_id'],
    ];

    protected string|array|null $listTenantScope = ['project.workspace', 'tenant_id'];

    protected array $listContextRelations = [
        'project.workspace',
    ];

    public function __construct(
        protected SwimlaneMermaidGenerator $mermaid = new SwimlaneMermaidGenerator,
    ) {
        $this->model = new SwimlaneFlow();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $elements = null;
            if (array_key_exists('elements', $data)) {
                $elements = is_array($data['elements'] ?? null) ? $data['elements'] : [];
            }

            $data = $this->normalizeFlowPayload($data);
            // Rows are the source of truth; do not persist JSON elements.
            $data['elements'] = null;

            /** @var SwimlaneFlow $flow */
            $flow = $this->model::create($this->filterFillable($data));

            if ($elements !== null) {
                $this->syncSwimlaneFlowSteps($flow, $elements);
            }

            return $flow->refresh();
        });
    }

    public function update($id, array $newData)
    {
        return DB::transaction(function () use ($id, $newData) {
            /** @var SwimlaneFlow $flow */
            $flow = $this->model::findOrFail($id);

            $elements = null;
            if (array_key_exists('elements', $newData)) {
                $elements = is_array($newData['elements'] ?? null) ? $newData['elements'] : [];
            }

            $newData = $this->normalizeFlowPayload($newData);
            $newData['elements'] = null;

            $flow->update($this->filterFillable($newData));

            if ($elements !== null) {
                $this->syncSwimlaneFlowSteps($flow->refresh(), $elements);
            }

            return $flow->refresh();
        });
    }

    public function editById($Id)
    {
        /** @var SwimlaneFlow $flow */
        $flow = $this->model::with('swimlaneFlowSteps')->findOrFail($Id);
        $payload = $flow->toArray();
        $payload['elements'] = $flow->elementsForEditor();

        return $this->editDto::from($payload);
    }

    public function getById($Id)
    {
        /** @var SwimlaneFlow $flow */
        $flow = $this->getCollectionWithBelongsTo(false, $Id);
        $flow->loadMissing('swimlaneFlowSteps');

        return $this->viewDto::from([
            ...$flow->toArray(),
            'project' => $flow->project,
            'status' => $flow->status,
            'elements' => $flow->elementsForEditor(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeFlowPayload(array $data): array
    {
        if (array_key_exists('direction', $data)) {
            $direction = strtoupper(trim((string) $data['direction']));
            $data['direction'] = $direction === 'LR' ? 'LR' : 'TB';
        }

        return $data;
    }

    /**
     * Replace swimlane_flow_steps for a flow from editor rows (position = first-entered order).
     *
     * @param  list<array<string, mixed>>  $elements
     */
    protected function syncSwimlaneFlowSteps(SwimlaneFlow $flow, array $elements): void
    {
        $rows = $this->mermaid->normalizeElements($elements);
        $projectId = (int) $flow->project_id;
        $keepIds = [];

        foreach ($rows as $index => $row) {
            $existingId = $row['id'] ?? null;
            /** @var SwimlaneFlowStep|null $step */
            $step = null;

            if ($existingId !== null) {
                $step = SwimlaneFlowStep::query()
                    ->where('swimlane_flow_id', $flow->id)
                    ->whereKey($existingId)
                    ->first();
            }

            $attributes = [
                'project_id' => $projectId,
                'position' => $index,
                'lane' => $row['lane'],
                'from_label' => $row['from'],
                'type' => $row['type'],
                'label' => $row['label'],
                'line_title' => $row['line_title'],
                'stakeholder_need_id' => $row['stakeholder_need_id'],
            ];

            if ($step !== null) {
                $step->fill($attributes);
                $step->save();
            } else {
                // Project-scoped PS-n via HasEntityNumber (leave number blank).
                $step = new SwimlaneFlowStep($attributes);
                $step->swimlane_flow_id = $flow->id;
                $step->save();
            }

            $keepIds[] = (int) $step->id;
        }

        $obsolete = SwimlaneFlowStep::query()
            ->where('swimlane_flow_id', $flow->id)
            ->when($keepIds !== [], fn ($q) => $q->whereNotIn('id', $keepIds))
            ->when($keepIds === [], fn ($q) => $q)
            ->get();

        foreach ($obsolete as $step) {
            $step->delete();
        }
    }
}
