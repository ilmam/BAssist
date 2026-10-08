<?php

namespace App\Repositories;

use App\Data\ScreenData;
use App\Data\ScreenViewData;
use App\Models\Screen;
use App\Models\ScreenElement;
use App\Services\SaltScreenAssembler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ScreenRepository extends BaseRepository
{
    public Model $model;

    public $editDto = ScreenData::class;

    public $viewDto = ScreenViewData::class;

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
        protected SaltScreenAssembler $salt = new SaltScreenAssembler,
    ) {
        $this->model = new Screen;
    }

    /** The view carries the realized requirements and the assembled Salt, so a reader sees what is drawn. */
    public function getById($Id)
    {
        $dto = parent::getById($Id);

        /** @var Screen $screen */
        $screen = $this->model::with(['functionalRequirements', 'screenElements'])->findOrFail($Id);

        return $this->viewDto::from([
            ...$dto->toArray(),
            'realizes' => $screen->functionalRequirements->pluck('code')->implode(', ') ?: null,
            'salt' => $this->salt->assembleScreen($screen),
        ]);
    }

    /** The edit form carries the element rows. */
    public function editById($Id)
    {
        /** @var Screen $screen */
        $screen = $this->model::with('screenElements')->findOrFail($Id);

        return $this->editDto::from([
            ...$screen->toArray(),
            'elements' => $screen->screenElements
                ->map(fn (ScreenElement $e) => [
                    'id' => (int) $e->id,
                    'key' => (string) $e->id,
                    'parent_key' => $e->parent_id !== null ? (string) $e->parent_id : null,
                    'row' => $e->row,
                    'kind' => $e->kind,
                    'label' => $e->label,
                    'functional_requirement_id' => $e->functional_requirement_id,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $requirementId = $this->extractRequirementId($data);
            $elements = $this->extractElements($data);

            /** @var Screen $screen */
            $screen = $this->model::create($this->filterFillable($data));

            if ($requirementId !== null) {
                $screen->functionalRequirements()->syncWithoutDetaching([$requirementId]);
            }

            if ($elements !== null) {
                $this->syncElements($screen, $elements);
            }

            return $screen;
        });
    }

    public function update($id, array $newData)
    {
        return DB::transaction(function () use ($id, $newData) {
            $requirementId = $this->extractRequirementId($newData);
            $elements = $this->extractElements($newData);

            /** @var Screen $screen */
            $screen = $this->model::findOrFail($id);
            $screen->update($this->filterFillable($newData));

            if ($requirementId !== null) {
                $screen->functionalRequirements()->syncWithoutDetaching([$requirementId]);
            }

            if ($elements !== null) {
                $this->syncElements($screen, $elements);
            }

            return $screen->refresh();
        });
    }

    protected function extractRequirementId(array &$data): ?int
    {
        $raw = $data['functional_requirement_id'] ?? null;
        unset($data['functional_requirement_id']);

        return $raw !== null && $raw !== '' && (int) $raw > 0 ? (int) $raw : null;
    }

    /**
     * @return list<array<string, mixed>>|null null = the payload did not carry rows: leave them alone
     */
    protected function extractElements(array &$data): ?array
    {
        $elements = $data['elements'] ?? null;
        unset($data['elements']);

        return is_array($elements) ? $elements : null;
    }

    /**
     * Replace the screen's element rows from the editor rows (order = row order).
     * Rows keep their id when they have one, so their history survives.
     *
     * @param  list<array<string, mixed>>  $elements
     */
    protected function syncElements(Screen $screen, array $elements): void
    {
        $keep = [];
        $ids = [];

        // Pass 1: save every row (existing rows keep their id); remember which key became which id.
        $saved = [];
        foreach ($this->salt->normalizeRows($elements) as $row) {
            $element = $row['id'] !== null
                ? ScreenElement::query()->where('screen_id', $screen->id)->whereKey($row['id'])->first()
                : null;

            $attributes = [
                'project_id' => $screen->project_id,
                'parent_id' => null,
                'position' => $row['position'],
                'row' => $row['row'],
                'kind' => $row['kind'],
                'label' => $row['label'],
                'functional_requirement_id' => $row['functional_requirement_id'],
            ];

            if ($element !== null) {
                $element->update($attributes);
            } else {
                $element = new ScreenElement($attributes);
                $element->screen_id = $screen->id;
                $element->save();
            }

            $keep[] = (int) $element->id;
            $ids[$row['key']] = (int) $element->id;
            $saved[] = [$element, $row['parent_key']];
        }

        // Pass 2: containers exist now, so rows can point at them.
        foreach ($saved as [$element, $parentKey]) {
            if ($parentKey !== null && isset($ids[$parentKey])) {
                $element->update(['parent_id' => $ids[$parentKey]]);
            }
        }

        ScreenElement::query()
            ->where('screen_id', $screen->id)
            ->whereNotIn('id', $keep)
            ->get()
            ->each(fn (ScreenElement $element) => $element->delete());
    }
}
