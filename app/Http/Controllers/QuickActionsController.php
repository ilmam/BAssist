<?php

namespace App\Http\Controllers;

use App\Models\Assumption;
use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\BusinessRule;
use App\Models\ChangeRequest;
use App\Models\Constraint;
use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\NonFunctionalRequirement;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Risk;
use App\Models\Stakeholder;
use App\Models\StakeholderNeed;
use App\Models\Status;
use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Power-user endpoints (#6): Ctrl+K search and inline / bulk status & priority edits.
 */
class QuickActionsController extends Controller
{
    /**
     * Searchable entities: model => [class, code prefix|null, title column].
     *
     * @var array<string, array{0: class-string<Model>, 1: string|null, 2: string}>
     */
    protected const SEARCHABLE = [
        'Project' => [Project::class, null, 'name'],
        'BusinessNeed' => [BusinessNeed::class, 'BN', 'title'],
        'BusinessObjective' => [BusinessObjective::class, 'BO', 'title'],
        'Stakeholder' => [Stakeholder::class, null, 'name'],
        'StakeholderNeed' => [StakeholderNeed::class, 'SN', 'title'],
        'Feature' => [Feature::class, 'FE', 'title'],
        'FunctionalRequirement' => [FunctionalRequirement::class, 'FR', 'title'],
        'NonFunctionalRequirement' => [NonFunctionalRequirement::class, 'NFR', 'title'],
        'ChangeRequest' => [ChangeRequest::class, 'CR', 'title'],
        'Risk' => [Risk::class, 'RSK', 'title'],
        'Assumption' => [Assumption::class, null, 'title'],
        'Constraint' => [Constraint::class, null, 'title'],
        'BusinessRule' => [BusinessRule::class, null, 'title'],
    ];

    /** Fields the quick editor may change, and the lookup table that validates them. */
    protected const QUICK_FIELDS = [
        'status_id' => Status::class,
        'priority_id' => Priority::class,
    ];

    protected const PER_TYPE = 5;

    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 1) {
            return response()->json(['results' => []]);
        }

        $results = [];
        $codeMatch = preg_match('/^([A-Za-z]{2,4})-?(\d+)$/', $query, $m) === 1;

        foreach (self::SEARCHABLE as $model => [$class, $prefix, $titleColumn]) {
            if (! array_key_exists($model, CrudEntityRegistry::all()) || ! entity_can($model, EntityAccess::VIEW)) {
                continue;
            }

            $builder = $class::query();
            $withProject = method_exists($class, 'project') && $model !== 'Project';
            if ($withProject) {
                $builder->with('project');
            }

            if ($codeMatch && $prefix !== null && strcasecmp($m[1], $prefix) === 0) {
                $builder->where('number', (int) $m[2]);
            } elseif ($codeMatch && $prefix !== null) {
                continue;
            } else {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $query).'%';
                $builder->where(function ($q) use ($like, $titleColumn, $model): void {
                    $q->where($titleColumn, 'like', $like);
                    if ($model === 'Project') {
                        $q->orWhere('code', 'like', $like);
                    }
                });
            }

            foreach ($builder->latest('updated_at')->limit(self::PER_TYPE)->get() as $record) {
                $results[] = $this->result($model, $record, $titleColumn, $withProject);
            }
        }

        return response()->json(['results' => $results]);
    }

    public function update(Request $request, string $model, int $id): JsonResponse
    {
        [$class, $field, $value] = $this->validated($request, $model);

        $record = $class::query()->findOrFail($id);
        $record->update([$field => $value]);

        return response()->json(['ok' => true, 'updated' => 1]);
    }

    public function bulkUpdate(Request $request, string $model): JsonResponse
    {
        [$class, $field, $value] = $this->validated($request, $model);

        $ids = collect((array) $request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->take(500)
            ->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages(['ids' => __('ui.quick_bulk_none')]);
        }

        // Per-record update keeps tenant scope and model events (audit, numbering) intact.
        $updated = 0;
        foreach ($class::query()->whereKey($ids->all())->get() as $record) {
            $record->update([$field => $value]);
            $updated++;
        }

        return response()->json(['ok' => true, 'updated' => $updated]);
    }

    /**
     * Options for the inline editor of a model (statuses / priorities with tones).
     *
     * @return array<string, list<array{id: int, name: string, tone: string}>>
     */
    public static function optionsFor(string $model): array
    {
        $class = self::modelClass($model);
        if ($class === null) {
            return [];
        }

        $fillable = (new $class)->getFillable();
        $options = [];
        foreach (self::QUICK_FIELDS as $field => $lookup) {
            if (! in_array($field, $fillable, true)) {
                continue;
            }
            $options[$field] = $lookup::query()
                ->orderBy('sort_order')
                ->get(['id', 'name', 'code'])
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'tone' => ui_status_tone((string) ($row->code ?: $row->name)),
                ])
                ->values()
                ->all();
        }

        return $options;
    }

    /**
     * @return class-string<Model>|null
     */
    protected static function modelClass(string $model): ?string
    {
        if (! array_key_exists($model, CrudEntityRegistry::all())) {
            return null;
        }
        $class = 'App\\Models\\'.$model;

        return class_exists($class) && is_subclass_of($class, Model::class) ? $class : null;
    }

    /**
     * @return array{0: class-string<Model>, 1: string, 2: int|null}
     */
    protected function validated(Request $request, string $model): array
    {
        $model = Str::studly($model);
        $class = self::modelClass($model);
        abort_if($class === null, 404);
        EntityAccess::authorize(auth()->user(), $model, EntityAccess::UPDATE);

        $field = (string) $request->input('field');
        $lookup = self::QUICK_FIELDS[$field] ?? null;
        if ($lookup === null || ! in_array($field, (new $class)->getFillable(), true)) {
            throw ValidationException::withMessages(['field' => __('ui.quick_field_not_editable')]);
        }

        $raw = $request->input('value');
        $value = ($raw === null || $raw === '') ? null : (int) $raw;
        if ($value !== null && ! $lookup::query()->whereKey($value)->exists()) {
            throw ValidationException::withMessages(['value' => __('ui.quick_value_invalid')]);
        }

        return [$class, $field, $value];
    }

    /**
     * @return array<string, mixed>
     */
    protected function result(string $model, Model $record, string $titleColumn, bool $withProject): array
    {
        $options = CrudEntityRegistry::all()[$model] ?? [];
        $isProject = $model === 'Project';

        return [
            'type' => Str::singular((string) ($options['nav_label'] ?? Str::headline($model))),
            'icon' => entity_icon($model),
            'code' => $record->getAttribute('code'),
            'title' => (string) $record->getAttribute($titleColumn),
            'meta' => $withProject ? $record->getAttribute('project')?->name : null,
            'url' => $isProject ? route('projects.dashboard', $record) : model_route($model, 'show', $record->getKey()),
            'modal' => $isProject ? null : model_modal_path($model, 'view', $record->getKey()),
        ];
    }
}
