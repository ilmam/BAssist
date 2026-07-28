<?php

namespace App\Repositories;

use App\Data\ArchitectureData;
use App\Data\ArchitectureViewData;
use App\Models\Architecture;
use App\Models\Project;
use App\Services\C4ArchitectureNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ArchitectureRepository extends BaseRepository
{
    public Model $model;

    public $editDto = ArchitectureData::class;

    public $viewDto = ArchitectureViewData::class;

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
        protected C4ArchitectureNormalizer $normalizer = new C4ArchitectureNormalizer,
    ) {
        $this->model = new Architecture();
    }

    public function findOrCreateForProject(Project $project): Architecture
    {
        $existing = Architecture::query()
            ->where('project_id', $project->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return Architecture::query()->create([
            'title' => $project->name.' architecture',
            'project_id' => $project->id,
            'description' => null,
            'elements' => [],
            'relationships' => [],
            'layout' => $this->normalizer->normalizeLayout([]),
        ]);
    }

    public function create(array $data)
    {
        $data = $this->normalizePayload($data);
        if (! array_key_exists('layout', $data)) {
            $data['layout'] = $this->normalizer->normalizeLayout([]);
        }
        $this->assertUniqueProject($data['project_id'] ?? null);

        return $this->model::create($this->filterFillable($data));
    }

    public function update($id, array $newData)
    {
        // Detect omitted layout before normalizePayload fills defaults (4/2).
        $hadExplicitLayout = $this->hasExplicitLayoutInput($newData);

        $newData = $this->normalizePayload($newData);

        /** @var Architecture $architecture */
        $architecture = $this->model::findOrFail($id);

        if (isset($newData['project_id']) && (int) $newData['project_id'] !== (int) $architecture->project_id) {
            $this->assertUniqueProject((int) $newData['project_id'], (int) $architecture->id);
        }

        // Spatie may pass layout=[] when the request omitted it — keep the stored value.
        if (! $hadExplicitLayout) {
            unset($newData['layout']);
        }

        $architecture->update($this->filterFillable($newData));

        return $architecture->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizePayload(array $data): array
    {
        if (array_key_exists('elements', $data)) {
            $raw = is_array($data['elements']) ? $data['elements'] : [];
            $data['elements'] = $this->normalizer->normalizeElements(
                $this->flattenStyleFields($raw)
            );
        }

        if (array_key_exists('relationships', $data)) {
            $raw = is_array($data['relationships']) ? $data['relationships'] : [];
            $data['relationships'] = $this->normalizer->normalizeRelationships($raw);
        }

        if ($this->hasExplicitLayoutInput($data)) {
            $raw = is_array($data['layout'] ?? null) ? $data['layout'] : [];
            if (isset($data['layout_shapes_per_row']) && ! isset($raw['shapes_per_row'])) {
                $raw['shapes_per_row'] = $data['layout_shapes_per_row'];
            }
            if (isset($data['layout_boundaries_per_row']) && ! isset($raw['boundaries_per_row'])) {
                $raw['boundaries_per_row'] = $data['layout_boundaries_per_row'];
            }
            $data['layout'] = $this->normalizer->normalizeLayout($raw);
        }

        unset($data['layout_shapes_per_row'], $data['layout_boundaries_per_row']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function hasExplicitLayoutInput(array $data): bool
    {
        if (isset($data['layout_shapes_per_row']) || isset($data['layout_boundaries_per_row'])) {
            return true;
        }

        if (! array_key_exists('layout', $data)) {
            return false;
        }

        if (! is_array($data['layout'])) {
            return false;
        }

        return array_key_exists('shapes_per_row', $data['layout'])
            || array_key_exists('boundaries_per_row', $data['layout']);
    }

    /**
     * Promote flat style fields from the form into style{} objects.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function flattenStyleFields(array $rows): array
    {
        return array_map(static function (array $row): array {
            $style = is_array($row['style'] ?? null) ? $row['style'] : [];
            foreach (['bg_color', 'font_color', 'border_color'] as $key) {
                if (array_key_exists($key, $row) && ! array_key_exists($key, $style)) {
                    $style[$key] = $row[$key];
                }
                unset($row[$key]);
            }
            $row['style'] = $style;

            if (isset($row['external'])) {
                $row['external'] = filter_var($row['external'], FILTER_VALIDATE_BOOLEAN)
                    || $row['external'] === '1'
                    || $row['external'] === 1
                    || $row['external'] === 'on';
            }

            if (isset($row['feature_ids']) && is_string($row['feature_ids'])) {
                $row['feature_ids'] = array_filter(array_map('intval', explode(',', $row['feature_ids'])));
            }

            return $row;
        }, $rows);
    }

    protected function assertUniqueProject(?int $projectId, ?int $ignoreId = null): void
    {
        if ($projectId === null || $projectId <= 0) {
            return;
        }

        $query = Architecture::query()->where('project_id', $projectId);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'project_id' => __('ui.architecture_one_per_project'),
            ]);
        }
    }
}
