<?php

namespace App\Repositories;

use App\Data\StateFlowData;
use App\Data\StateFlowViewData;
use App\Models\StateFlow;
use App\Services\StateDiagramMermaidGenerator;
use Illuminate\Database\Eloquent\Model;

class StateFlowRepository extends BaseRepository
{
    public Model $model;

    public $editDto = StateFlowData::class;

    public $viewDto = StateFlowViewData::class;

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
        protected StateDiagramMermaidGenerator $mermaid = new StateDiagramMermaidGenerator,
    ) {
        $this->model = new StateFlow();
    }

    public function editById($Id)
    {
        /** @var StateFlow $flow */
        $flow = $this->model::findOrFail($Id);
        $dto = StateFlowData::from($flow);
        $split = $this->mermaid->splitTerminals($flow->transitions ?? []);

        return StateFlowData::from([
            ...$dto->toArray(),
            'transitions' => $split['transitions'],
            'initial_state' => $split['initial'],
            'final_states' => implode(', ', $split['finals']),
        ]);
    }

    public function create(array $data)
    {
        $data = $this->normalizeTransitionsPayload($data);

        return $this->model::create($this->filterFillable($data));
    }

    public function update($id, array $newData)
    {
        $newData = $this->normalizeTransitionsPayload($newData);

        /** @var StateFlow $flow */
        $flow = $this->model::findOrFail($id);
        $flow->update($this->filterFillable($newData));

        return $flow->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeTransitionsPayload(array $data): array
    {
        $initial = $data['initial_state'] ?? null;
        $finals = $data['final_states'] ?? null;
        unset($data['initial_state'], $data['final_states']);

        if (! array_key_exists('transitions', $data) && $initial === null && $finals === null) {
            return $data;
        }

        $rows = is_array($data['transitions'] ?? null) ? $data['transitions'] : [];
        $data['transitions'] = $this->mermaid->composeFromForm($rows, $initial, $finals);

        return $data;
    }
}
