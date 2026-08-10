<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\NonFunctionalRequirement;
use App\Support\DtoMetadata;
use App\Support\EntityAccess;
use App\Support\ProjectContext;
use App\Support\RepositoryResolver;
use App\Support\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SolutionRequirementsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeView();

        $tenantId = auth()->user()?->tenant_id;
        $projectId = $this->resolveProjectId($request);
        $workspaceId = $this->resolveWorkspaceId($request);

        $sections = [];

        if (entity_can('Feature', EntityAccess::VIEW)) {
            $sections[] = $this->section(
                model: 'Feature',
                label: __('ui.features'),
                description: __('ui.solution_requirements_features_help'),
                icon: entity_icon('Feature'),
                projectId: $projectId,
                workspaceId: $workspaceId,
                tenantId: $tenantId,
                query: $this->baseQuery(Feature::query(), $projectId, $workspaceId, $tenantId),
            );
        }

        if (entity_can('FunctionalRequirement', EntityAccess::VIEW)) {
            $sections[] = $this->section(
                model: 'FunctionalRequirement',
                label: __('ui.functional_requirements'),
                description: __('ui.solution_requirements_functional_requirements_help'),
                icon: entity_icon('FunctionalRequirement'),
                projectId: $projectId,
                workspaceId: $workspaceId,
                tenantId: $tenantId,
                query: $this->baseQuery(FunctionalRequirement::query(), $projectId, $workspaceId, $tenantId),
            );
        }

        if (entity_can('NonFunctionalRequirement', EntityAccess::VIEW)) {
            $sections[] = $this->section(
                model: 'NonFunctionalRequirement',
                label: __('ui.non_functional_requirements'),
                description: __('ui.solution_requirements_non_functional_requirements_help'),
                icon: entity_icon('NonFunctionalRequirement'),
                projectId: $projectId,
                workspaceId: $workspaceId,
                tenantId: $tenantId,
                query: $this->baseQuery(NonFunctionalRequirement::query(), $projectId, $workspaceId, $tenantId),
            );
        }

        $filters = array_filter([
            'project_id' => $projectId,
            'workspace_id' => $workspaceId,
        ], fn ($v) => $v !== null && $v !== '');

        $indexUrl = route('solution_requirements.index');
        $clearUrl = ! empty($filters['project_id'])
            ? route('solution_requirements.index', array_filter([
                'workspace_id' => $filters['workspace_id'] ?? null,
                'clear_project' => 1,
            ]))
            : null;

        return view('pages.solution_requirements.index', [
            'sections' => $sections,
            'filters' => $filters,
            'filterAction' => $indexUrl,
            'filterClearUrl' => $clearUrl,
            'allowedListFilters' => ['project_id'],
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

        $scopeQuery = array_filter([
            'workspace_id' => $workspaceId,
            'project_id' => $projectId,
        ], fn ($v) => $v !== null);

        $indexUrl = model_route($model, 'index');
        if ($scopeQuery !== []) {
            $indexUrl .= '?'.http_build_query($scopeQuery);
        }

        $ajaxUrl = route('api.'.Str::snake($model).'.index', ['modelName' => $model]);
        if ($scopeQuery !== []) {
            $ajaxUrl .= (str_contains($ajaxUrl, '?') ? '&' : '?').http_build_query($scopeQuery);
        }

        $createModalUrl = entity_can($model, EntityAccess::CREATE)
            ? model_modal_path($model, 'create')
            : null;
        if ($createModalUrl && $scopeQuery !== []) {
            $createModalUrl .= (str_contains($createModalUrl, '?') ? '&' : '?').http_build_query($scopeQuery);
        }

        $repository = RepositoryResolver::make($model);
        $columns = DtoMetadata::for($repository->viewDto)->listColumns(withPrefix: true);

        return [
            'model' => $model,
            'label' => $label,
            'description' => $description,
            'icon' => $icon,
            'count' => $count,
            'columns' => $columns,
            'ajax_url' => $ajaxUrl,
            'index_url' => $indexUrl,
            'create_modal_url' => $createModalUrl,
            'can_create' => entity_can($model, EntityAccess::CREATE),
            'table_id' => 'hub-'.Str::snake($model),
            'page_length' => 10,
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
            || EntityAccess::can($user, 'FunctionalRequirement', EntityAccess::VIEW)
            || EntityAccess::can($user, 'NonFunctionalRequirement', EntityAccess::VIEW);

        if (! $canView) {
            EntityAccess::authorize($user, 'Feature', EntityAccess::VIEW);
        }
    }
}
