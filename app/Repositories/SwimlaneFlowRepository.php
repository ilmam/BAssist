<?php

namespace App\Repositories;

use App\Data\SwimlaneFlowData;
use App\Data\SwimlaneFlowViewData;
use App\Models\SwimlaneFlow;
use App\Services\SwimlaneMermaidGenerator;
use Illuminate\Database\Eloquent\Model;

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
        $data = $this->normalizeElementsPayload($data);

        return $this->model::create($this->filterFillable($data));
    }

    public function update($id, array $newData)
    {
        $newData = $this->normalizeElementsPayload($newData);

        /** @var SwimlaneFlow $flow */
        $flow = $this->model::findOrFail($id);
        $flow->update($this->filterFillable($newData));

        return $flow->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeElementsPayload(array $data): array
    {
        if (array_key_exists('direction', $data)) {
            $direction = strtoupper(trim((string) $data['direction']));
            $data['direction'] = $direction === 'LR' ? 'LR' : 'TB';
        }

        if (! array_key_exists('elements', $data)) {
            return $data;
        }

        $rows = is_array($data['elements'] ?? null) ? $data['elements'] : [];
        $data['elements'] = $this->mermaid->normalizeElements($rows);

        return $data;
    }
}
