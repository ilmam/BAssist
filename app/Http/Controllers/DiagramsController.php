<?php

namespace App\Http\Controllers;

use App\Models\Architecture;
use App\Models\Project;
use App\Models\StateFlow;
use App\Models\SwimlaneFlow;
use App\Support\EntityAccess;
use App\Support\ProjectContext;
use App\Support\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DiagramsController extends Controller
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

        if (entity_can('Architecture', EntityAccess::VIEW)) {
            $sections[] = $this->architectureSection($projectId, $workspaceId, $tenantId, $projects);
        }

        if (entity_can('StateFlow', EntityAccess::VIEW)) {
            $sections[] = $this->section(
                model: 'StateFlow',
                label: __('ui.state_flows'),
                description: __('ui.diagrams_state_flows_help'),
                icon: entity_icon('StateFlow'),
                projectId: $projectId,
                workspaceId: $workspaceId,
                tenantId: $tenantId,
            );
        }

        if (entity_can('SwimlaneFlow', EntityAccess::VIEW)) {
            $sections[] = $this->section(
                model: 'SwimlaneFlow',
                label: __('ui.swimlane_flows'),
                description: __('ui.diagrams_swimlane_flows_help'),
                icon: entity_icon('SwimlaneFlow'),
                projectId: $projectId,
                workspaceId: $workspaceId,
                tenantId: $tenantId,
            );
        }

        return view('pages.diagrams.index', [
            'sections' => $sections,
            'projects' => $projects,
            'filters' => [
                'project_id' => $projectId,
                'workspace_id' => $workspaceId,
            ],
        ]);
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array<string, mixed>
     */
    protected function architectureSection(?int $projectId, ?int $workspaceId, mixed $tenantId, Collection $projects): array
    {
        $query = Architecture::query()->with(['project', 'status'])
            ->when($tenantId !== null, fn ($q) => $q->whereHas(
                'project.workspace',
                fn ($w) => $w->where('tenant_id', $tenantId)
            ))
            ->when($workspaceId !== null, fn ($q) => $q->whereHas(
                'project',
                fn ($p) => $p->where('workspace_id', $workspaceId)
            ))
            ->when($projectId !== null, fn ($q) => $q->where('project_id', $projectId));

        $items = (clone $query)->orderBy('title')->limit(50)->get();
        $canUpdate = entity_can('Architecture', EntityAccess::UPDATE);
        $canCreate = entity_can('Architecture', EntityAccess::CREATE);

        $openUrl = null;
        if ($projectId !== null) {
            $openUrl = route('architectures.for-project', $projectId);
        }

        return [
            'model' => 'Architecture',
            'label' => __('ui.architecture_c4'),
            'description' => __('ui.diagrams_architecture_help'),
            'icon' => entity_icon('Architecture'),
            'count' => $items->count(),
            'items' => $items,
            'index_url' => $openUrl ?? model_route('Architecture', 'index'),
            'create_modal_url' => null,
            'can_create' => false,
            'architecture_open_url' => $openUrl,
            'architecture_can_open' => $projectId !== null && ($canUpdate || $canCreate || entity_can('Architecture', EntityAccess::VIEW)),
            'is_architecture' => true,
        ];
    }

    /**
     * @return array{
     *     model: string,
     *     label: string,
     *     description: string,
     *     icon: string,
     *     count: int,
     *     items: Collection<int, object>,
     *     index_url: string,
     *     create_modal_url: string|null,
     *     can_create: bool
     * }
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
        $query = $model === 'StateFlow'
            ? StateFlow::query()->with(['project', 'status'])
            : SwimlaneFlow::query()->with(['project', 'status']);

        $query
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
            ->get();

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
            'is_architecture' => false,
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

        $canView = EntityAccess::can($user, 'Architecture', EntityAccess::VIEW)
            || EntityAccess::can($user, 'StateFlow', EntityAccess::VIEW)
            || EntityAccess::can($user, 'SwimlaneFlow', EntityAccess::VIEW);

        if (! $canView) {
            EntityAccess::authorize($user, 'StateFlow', EntityAccess::VIEW);
        }
    }
}
