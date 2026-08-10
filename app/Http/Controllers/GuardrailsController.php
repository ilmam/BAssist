<?php

namespace App\Http\Controllers;

use App\Models\Assumption;
use App\Models\BusinessRule;
use App\Models\Constraint;
use App\Support\DtoMetadata;
use App\Support\EntityAccess;
use App\Support\ProjectContext;
use App\Support\RepositoryResolver;
use App\Support\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GuardrailsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeView();

        $tenantId = auth()->user()?->tenant_id;
        $projectId = $this->resolveProjectId($request);
        $workspaceId = $this->resolveWorkspaceId($request);

        $sections = [];

        if (entity_can('Assumption', EntityAccess::VIEW)) {
            $sections[] = $this->section(
                model: 'Assumption',
                label: __('ui.assumptions'),
                description: __('ui.guardrails_assumptions_help'),
                icon: entity_icon('Assumption'),
                projectId: $projectId,
                workspaceId: $workspaceId,
                tenantId: $tenantId,
            );
        }

        if (entity_can('Constraint', EntityAccess::VIEW)) {
            $sections[] = $this->section(
                model: 'Constraint',
                label: __('ui.constraints'),
                description: __('ui.guardrails_constraints_help'),
                icon: entity_icon('Constraint'),
                projectId: $projectId,
                workspaceId: $workspaceId,
                tenantId: $tenantId,
            );
        }

        if (entity_can('BusinessRule', EntityAccess::VIEW)) {
            $sections[] = $this->section(
                model: 'BusinessRule',
                label: __('ui.business_rules'),
                description: __('ui.guardrails_business_rules_help'),
                icon: entity_icon('BusinessRule'),
                projectId: $projectId,
                workspaceId: $workspaceId,
                tenantId: $tenantId,
            );
        }

        $filters = array_filter([
            'project_id' => $projectId,
            'workspace_id' => $workspaceId,
        ], fn ($v) => $v !== null && $v !== '');

        $indexUrl = route('guardrails.index');
        $clearUrl = ! empty($filters['project_id'])
            ? route('guardrails.index', array_filter([
                'workspace_id' => $filters['workspace_id'] ?? null,
                'clear_project' => 1,
            ]))
            : null;

        return view('pages.guardrails.index', [
            'sections' => $sections,
            'filters' => $filters,
            'filterAction' => $indexUrl,
            'filterClearUrl' => $clearUrl,
            'allowedListFilters' => ['project_id'],
        ]);
    }

    /**
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
    ): array {
        $count = $this->baseQuery($model, $projectId, $workspaceId, $tenantId)->count();

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
        ];
    }

    protected function baseQuery(string $model, ?int $projectId, ?int $workspaceId, mixed $tenantId): Builder
    {
        $query = match ($model) {
            'Assumption' => Assumption::query(),
            'Constraint' => Constraint::query(),
            'BusinessRule' => BusinessRule::query(),
            default => throw new \InvalidArgumentException("Unknown guardrail model [{$model}]."),
        };

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

        $canView = EntityAccess::can($user, 'Assumption', EntityAccess::VIEW)
            || EntityAccess::can($user, 'Constraint', EntityAccess::VIEW)
            || EntityAccess::can($user, 'BusinessRule', EntityAccess::VIEW);

        if (! $canView) {
            EntityAccess::authorize($user, 'Assumption', EntityAccess::VIEW);
        }
    }
}
