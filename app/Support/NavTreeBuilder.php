<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds workspace → project → artifact sidebar navigation for the current tenant.
 */
class NavTreeBuilder
{
    /**
     * Models that form the hierarchy containers (not leaf artifact links).
     *
     * @var list<string>
     */
    public const CONTAINER_MODELS = ['Workspace', 'Project'];

    public function __construct(
        protected WorkspaceContext $workspaceContext,
        protected ProjectContext $projectContext,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function build(): array
    {
        $user = auth()->user();
        if ($user === null || $user->tenant_id === null) {
            return $this->managementFallback();
        }

        $canViewWorkspace = EntityAccess::can($user, 'Workspace', EntityAccess::VIEW);
        $canViewProject = EntityAccess::can($user, 'Project', EntityAccess::VIEW);
        $artifacts = $this->artifactItems();

        if (! $canViewWorkspace && ! $canViewProject && $artifacts === []) {
            return [];
        }

        $workspaces = Workspace::query()
            ->where('tenant_id', $user->tenant_id)
            ->with(['projects' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name']);

        $activeWorkspaceId = $this->workspaceContext->id();
        $activeProjectId = $this->projectContext->id();
        $hierarchyConfig = config('navigation.hierarchy', []);

        $children = [];

        if ($canViewWorkspace) {
            $children[] = [
                'label' => $hierarchyConfig['all_workspaces_label'] ?? 'All Workspaces',
                'route' => model_route_name('Workspace', 'index'),
                'query' => ['clear_workspace' => 1],
            ];
        }

        foreach ($workspaces as $workspace) {
            $workspaceChildren = $this->workspaceChildren(
                $workspace,
                $artifacts,
                $canViewProject,
                $activeProjectId,
            );

            if ($workspaceChildren === [] && ! $canViewWorkspace && ! $canViewProject) {
                continue;
            }

            $children[] = [
                'label' => $workspace->name,
                'route' => model_route_name('Project', 'index'),
                'query' => ['workspace_id' => $workspace->id],
                'icon' => $hierarchyConfig['workspace_icon'] ?? 'folder',
                'icon_v8' => $hierarchyConfig['workspace_icon_v8'] ?? ($hierarchyConfig['workspace_icon'] ?? 'folder'),
                'context' => ['workspace_id' => (int) $workspace->id],
                'children' => $workspaceChildren,
                'force_open' => $activeWorkspaceId !== null && (int) $activeWorkspaceId === (int) $workspace->id,
            ];
        }

        if ($children === []) {
            return $this->managementFallback();
        }

        return [[
            'label' => $hierarchyConfig['label'] ?? 'Workspaces',
            'icon' => $hierarchyConfig['icon'] ?? 'folder',
            'icon_v8' => $hierarchyConfig['icon_v8'] ?? ($hierarchyConfig['icon'] ?? 'folder'),
            'children' => $children,
        ]];
    }

    /**
     * @param  list<array<string, mixed>>  $artifacts
     * @return list<array<string, mixed>>
     */
    protected function workspaceChildren(
        Workspace $workspace,
        array $artifacts,
        bool $canViewProject,
        ?int $activeProjectId,
    ): array {
        $children = [];

        if ($canViewProject) {
            $children[] = [
                'label' => config('navigation.hierarchy.all_projects_label', 'All Projects'),
                'route' => model_route_name('Project', 'index'),
                'query' => ['workspace_id' => $workspace->id],
                'context' => ['workspace_id' => (int) $workspace->id],
            ];
        }

        /** @var Collection<int, Project> $projects */
        $projects = $workspace->projects;

        foreach ($projects as $project) {
            $projectQuery = [
                'workspace_id' => $workspace->id,
                'project_id' => $project->id,
            ];

            $artifactChildren = [];
            foreach ($artifacts as $artifact) {
                $artifactChildren[] = array_merge($artifact, [
                    'query' => $projectQuery,
                    'context' => [
                        'workspace_id' => (int) $workspace->id,
                        'project_id' => (int) $project->id,
                    ],
                ]);
            }

            $children[] = [
                'label' => $project->name,
                'route' => 'projects.dashboard',
                'route_params' => ['project' => $project->id],
                'query' => $projectQuery,
                'icon' => config('navigation.hierarchy.project_icon', 'abstract-26'),
                'icon_v8' => config('navigation.hierarchy.project_icon_v8', config('navigation.hierarchy.project_icon', 'abstract-26')),
                'context' => [
                    'workspace_id' => (int) $workspace->id,
                    'project_id' => (int) $project->id,
                ],
                'children' => $artifactChildren,
                'force_open' => $activeProjectId !== null && (int) $activeProjectId === (int) $project->id,
            ];
        }

        return $children;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function artifactItems(): array
    {
        $items = [];

        foreach (CrudEntityRegistry::all() as $model => $options) {
            if (! ($options['nav'] ?? false)) {
                continue;
            }

            if (in_array($model, self::CONTAINER_MODELS, true)) {
                continue;
            }

            if (! empty($options['nav_container'])) {
                continue;
            }

            if (! entity_can($model, EntityAccess::VIEW)) {
                continue;
            }

            $items[] = [
                'label' => $options['nav_label'] ?? Str::plural($model),
                'route' => model_route_name($model, 'index'),
                'icon' => $options['nav_icon'] ?? 'element-11',
                'icon_v8' => $options['nav_icon_v8'] ?? ($options['nav_icon'] ?? 'element-11'),
            ];
        }

        foreach (config('navigation.hierarchy.project_artifacts', []) as $artifact) {
            if (! is_array($artifact) || ! nav_item_is_visible($artifact)) {
                continue;
            }

            $route = $artifact['route'] ?? null;
            if (! is_string($route) || $route === '') {
                continue;
            }

            $items[] = [
                'label' => $artifact['label'] ?? $route,
                'route' => $route,
                'icon' => $artifact['icon'] ?? 'element-11',
                'icon_v8' => $artifact['icon_v8'] ?? ($artifact['icon'] ?? 'element-11'),
            ];
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function managementFallback(): array
    {
        $children = [];

        foreach (self::CONTAINER_MODELS as $model) {
            $options = CrudEntityRegistry::all()[$model] ?? null;
            if ($options === null || ! ($options['nav'] ?? false)) {
                continue;
            }

            if (! entity_can($model, EntityAccess::VIEW)) {
                continue;
            }

            $children[] = [
                'label' => $options['nav_label'] ?? Str::plural($model),
                'route' => model_route_name($model, 'index'),
                'icon' => $options['nav_icon'] ?? 'category',
                'icon_v8' => $options['nav_icon_v8'] ?? ($options['nav_icon'] ?? 'category'),
            ];
        }

        if ($children === []) {
            return [];
        }

        $hierarchyConfig = config('navigation.hierarchy', []);

        return [[
            'label' => $hierarchyConfig['label'] ?? 'Workspaces',
            'icon' => $hierarchyConfig['icon'] ?? 'folder',
            'icon_v8' => $hierarchyConfig['icon_v8'] ?? ($hierarchyConfig['icon'] ?? 'folder'),
            'children' => $children,
        ]];
    }
}
