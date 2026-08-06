<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ScopeItem;
use App\Models\StrategicBaseline;
use App\Support\EntityAccess;
use App\Support\ProjectContext;
use App\Support\ScopeItemDirection;
use App\Support\StrategicBaselineStatus;
use App\Support\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StrategyController extends Controller
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

        if (entity_can('StrategicBaseline', EntityAccess::VIEW)) {
            $sections[] = $this->baselineSection($projectId, $workspaceId, $tenantId);
        }

        if (entity_can('ScopeItem', EntityAccess::VIEW)) {
            $sections[] = $this->scopeSection($projectId, $workspaceId, $tenantId);
        }

        return view('pages.strategy.index', [
            'sections' => $sections,
            'projects' => $projects,
            'filters' => [
                'project_id' => $projectId,
                'workspace_id' => $workspaceId,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function baselineSection(?int $projectId, ?int $workspaceId, mixed $tenantId): array
    {
        $query = StrategicBaseline::query()->with('project')
            ->when($tenantId !== null, fn ($q) => $q->whereHas(
                'project.workspace',
                fn ($w) => $w->where('tenant_id', $tenantId)
            ))
            ->when($workspaceId !== null, fn ($q) => $q->whereHas(
                'project',
                fn ($p) => $p->where('workspace_id', $workspaceId)
            ))
            ->when($projectId !== null, fn ($q) => $q->where('project_id', $projectId));

        $items = (clone $query)->orderByDesc('updated_at')->limit(50)->get()
            ->map(function (StrategicBaseline $item) {
                $item->row_title = $item->project?->name ?? __('ui.strategic_baseline');
                $item->row_status = StrategicBaselineStatus::label((string) $item->status);

                return $item;
            });

        $canUpdate = entity_can('StrategicBaseline', EntityAccess::UPDATE);
        $canCreate = entity_can('StrategicBaseline', EntityAccess::CREATE);

        $openUrl = $projectId !== null
            ? route('strategic_baselines.for-project', $projectId)
            : null;

        return [
            'model' => 'StrategicBaseline',
            'label' => __('ui.strategic_baseline'),
            'description' => __('ui.strategy_baseline_help'),
            'icon' => entity_icon('StrategicBaseline'),
            'count' => $items->count(),
            'items' => $items,
            'index_url' => $openUrl ?? model_route('StrategicBaseline', 'index'),
            'create_modal_url' => null,
            'can_create' => false,
            'baseline_open_url' => $openUrl,
            'baseline_can_open' => $projectId !== null && ($canUpdate || $canCreate || entity_can('StrategicBaseline', EntityAccess::VIEW)),
            'is_baseline' => true,
            'status_column' => __('ui.status'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function scopeSection(?int $projectId, ?int $workspaceId, mixed $tenantId): array
    {
        $query = ScopeItem::query()->with('project')
            ->when($tenantId !== null, fn ($q) => $q->whereHas(
                'project.workspace',
                fn ($w) => $w->where('tenant_id', $tenantId)
            ))
            ->when($workspaceId !== null, fn ($q) => $q->whereHas(
                'project',
                fn ($p) => $p->where('workspace_id', $workspaceId)
            ))
            ->when($projectId !== null, fn ($q) => $q->where('project_id', $projectId));

        $count = (clone $query)->count();
        $items = (clone $query)
            ->orderByDesc('updated_at')
            ->orderBy('title')
            ->limit(50)
            ->get()
            ->map(function (ScopeItem $item) {
                $item->row_title = $item->title;
                $item->row_status = ScopeItemDirection::label((string) $item->direction);

                return $item;
            });

        $scopeQuery = array_filter([
            'workspace_id' => $workspaceId,
            'project_id' => $projectId,
        ], fn ($v) => $v !== null);

        $indexUrl = model_route('ScopeItem', 'index');
        if ($scopeQuery !== []) {
            $indexUrl .= '?'.http_build_query($scopeQuery);
        }

        return [
            'model' => 'ScopeItem',
            'label' => __('ui.scope_items'),
            'description' => __('ui.strategy_scope_help'),
            'icon' => entity_icon('ScopeItem'),
            'count' => $count,
            'items' => $items,
            'index_url' => $indexUrl,
            'create_modal_url' => entity_can('ScopeItem', EntityAccess::CREATE)
                ? model_modal_path('ScopeItem', 'create')
                : null,
            'can_create' => entity_can('ScopeItem', EntityAccess::CREATE),
            'is_baseline' => false,
            'status_column' => __('ui.direction'),
        ];
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

        $canView = EntityAccess::can($user, 'StrategicBaseline', EntityAccess::VIEW)
            || EntityAccess::can($user, 'ScopeItem', EntityAccess::VIEW);

        if (! $canView) {
            EntityAccess::authorize($user, 'StrategicBaseline', EntityAccess::VIEW);
        }
    }
}
