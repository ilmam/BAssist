<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Support\EntityAccess;
use App\Support\ProjectContext;
use App\Support\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SolutionRequirementsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeView();

        $tenantId = auth()->user()?->tenant_id;
        $projectId = $this->resolveProjectId($request);
        $workspaceId = $this->resolveWorkspaceId($request);

        $projects = Project::query()
            ->with('workspace')
            ->when($tenantId !== null, fn ($q) => $q->whereHas(
                'workspace',
                fn ($w) => $w->where('tenant_id', $tenantId)
            ))
            ->when($workspaceId !== null, fn ($q) => $q->where('workspace_id', $workspaceId))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'workspace_id']);

        $sections = [];

        if (entity_can('FunctionalRequirement', EntityAccess::VIEW)) {
            $sections[] = $this->section(
                model: 'FunctionalRequirement',
                label: __('ui.functional_requirements'),
                description: __('ui.solution_requirements_functional_requirements_help'),
                icon: 'subtitle',
                projectId: $projectId,
                workspaceId: $workspaceId,
                tenantId: $tenantId,
                query: $this->baseQuery(FunctionalRequirement::query(), $projectId, $workspaceId, $tenantId),
            );
        }

        if (entity_can('Feature', EntityAccess::VIEW)) {
            $sections[] = $this->section(
                model: 'Feature',
                label: __('ui.features'),
                description: __('ui.solution_requirements_features_help'),
                icon: 'category',
                projectId: $projectId,
                workspaceId: $workspaceId,
                tenantId: $tenantId,
                query: $this->baseQuery(Feature::query(), $projectId, $workspaceId, $tenantId),
            );
        }

        return view('pages.solution_requirements.index', [
            'sections' => $sections,
            'projects' => $projects,
            'filters' => [
                'project_id' => $projectId,
                'workspace_id' => $workspaceId,
            ],
        ]);
    }

    /**
     * @param  Builder<Model>  $query
     * @return array<string, mixed>
     */
    protected function section(
        string $model,
        string $label,
        string $description,
        string $icon,
        ?int $projectId,
        ?int $workspaceId,
        mixed $tenantId,
        Builder $query,
    ): array {
        $count = (clone $query)->count();
        $items = (clone $query)
            ->with(['project', 'status'])
            ->orderByDesc('updated_at')
            ->orderBy('title')
            ->limit(50)
            ->get()
            ->map(function (Model $item) {
                $item->status_label = $item->status?->name ?? '—';

                return $item;
            });

        $scopeQuery = array_filter([
            'workspace_id' => $workspaceId,
            'project_id' => $projectId,
        ], fn ($v) => $v !== null);

        $indexUrl = model_route($model, 'index');
        if ($scopeQuery !== []) {
            $indexUrl .= '?'.http_build_query($scopeQuery);
        }

        return [
            'model' => $model,
            'label' => $label,
            'description' => $description,
            'icon' => $icon,
            'count' => $count,
            'items' => $items,
            'index_url' => $indexUrl,
            'create_modal_url' => entity_can($model, EntityAccess::CREATE)
                ? model_modal_path($model, 'create')
                : null,
            'can_create' => entity_can($model, EntityAccess::CREATE),
            'placeholder' => false,
            'help_topic' => null,
        ];
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function baseQuery(Builder $query, ?int $projectId, ?int $workspaceId, mixed $tenantId): Builder
    {
        return $query
            ->when($tenantId !== null, fn ($q) => $q->whereHas(
                'project.workspace',
                fn ($w) => $w->where('tenant_id', $tenantId)
            ))
            ->when($workspaceId !== null, fn ($q) => $q->whereHas(
                'project',
                fn ($p) => $p->where('workspace_id', $workspaceId)
            ))
            ->when($projectId !== null, fn ($q) => $q->where('project_id', $projectId));
    }

    protected function resolveProjectId(Request $request): ?int
    {
        $raw = $request->query('project_id');
        if ($raw !== null && $raw !== '') {
            return (int) $raw;
        }

        return app(ProjectContext::class)->id();
    }

    protected function resolveWorkspaceId(Request $request): ?int
    {
        $raw = $request->query('workspace_id');
        if ($raw !== null && $raw !== '') {
            return (int) $raw;
        }

        return app(WorkspaceContext::class)->id();
    }

    protected function authorizeView(): void
    {
        $user = auth()->user();

        $canView = EntityAccess::can($user, 'Feature', EntityAccess::VIEW)
            || EntityAccess::can($user, 'FunctionalRequirement', EntityAccess::VIEW);

        if (! $canView) {
            EntityAccess::authorize($user, 'Feature', EntityAccess::VIEW);
        }
    }
}
