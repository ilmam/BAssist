<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds workspace → project → BABOK folder → artifact sidebar navigation.
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
        protected NavFolderProgress $folderProgress,
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
        $folderTemplates = $this->folderTemplates();

        if (! $canViewWorkspace && ! $canViewProject && $folderTemplates === []) {
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
                $folderTemplates,
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
     * @param  list<array<string, mixed>>  $folderTemplates
     * @return list<array<string, mixed>>
     */
    protected function workspaceChildren(
        Workspace $workspace,
        array $folderTemplates,
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

            $isActiveProject = $activeProjectId !== null && (int) $activeProjectId === (int) $project->id;

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
                'children' => $this->projectFolderItems($project, $folderTemplates, $projectQuery, $isActiveProject),
                'force_open' => $isActiveProject,
            ];
        }

        return $children;
    }

    /**
     * @param  list<array<string, mixed>>  $folderTemplates
     * @param  array<string, int>  $projectQuery
     * @return list<array<string, mixed>>
     */
    protected function projectFolderItems(
        Project $project,
        array $folderTemplates,
        array $projectQuery,
        bool $computeBadges,
    ): array {
        $folders = [];

        foreach ($folderTemplates as $folder) {
            $children = [];

            foreach ($folder['children'] as $childDef) {
                $leaf = $this->resolveLeaf($childDef, $project);
                if ($leaf === null) {
                    continue;
                }

                $merged = array_merge($leaf, [
                    'context' => [
                        'workspace_id' => (int) $projectQuery['workspace_id'],
                        'project_id' => (int) $project->id,
                    ],
                ]);

                // for-project style routes already carry the project in route_params.
                if (empty($leaf['route_params'])) {
                    $merged['query'] = $projectQuery;
                }

                $children[] = $merged;
            }

            if ($children === []) {
                continue;
            }

            $item = [
                'type' => 'folder',
                'folder_key' => $folder['key'] ?? null,
                'label' => $folder['label'],
                'icon' => $folder['icon'] ?? 'folder',
                'icon_v8' => $folder['icon_v8'] ?? ($folder['icon'] ?? 'folder'),
                'title' => trim(($folder['babok'] ?? '').' — '.($folder['purpose'] ?? ''), ' —'),
                'badge_tone' => $folder['badge_tone'] ?? null,
                'children' => $children,
                // Open when a child is active; do not force all folders open.
                'force_open' => false,
            ];

            if ($computeBadges && config('navigation.hierarchy.show_folder_badges', false)) {
                $badge = $this->folderProgress->forFolder($project, $folder, $children);
                if ($badge !== null) {
                    $item['badge'] = $badge['label'];
                    $item['badge_title'] = $badge['title'];
                }
            }

            $folders[] = $item;
        }

        return $folders;
    }

    /**
     * @param  array<string, mixed>  $childDef
     * @return array<string, mixed>|null
     */
    protected function resolveLeaf(array $childDef, ?Project $project = null): ?array
    {
        if (isset($childDef['entity']) && is_string($childDef['entity'])) {
            $model = $childDef['entity'];
            $options = CrudEntityRegistry::all()[$model] ?? null;
            if ($options === null || ! empty($options['disabled'])) {
                return null;
            }
            if (! entity_can($model, EntityAccess::VIEW)) {
                return null;
            }

            return [
                'label' => $options['nav_label'] ?? Str::plural($model),
                'route' => model_route_name($model, 'index'),
                'icon' => entity_icon($model, $options['nav_icon'] ?? 'element-11'),
                'icon_v8' => entity_icon($model, $options['nav_icon_v8'] ?? ($options['nav_icon'] ?? 'element-11')),
                'entity' => $model,
            ];
        }

        if (! nav_item_is_visible($childDef)) {
            return null;
        }

        $route = $childDef['route'] ?? null;
        if (! is_string($route) || $route === '') {
            return null;
        }

        $surfaceKey = match ($route) {
            'guardrails.index' => 'guardrails',
            'solution_requirements.index' => 'solution_requirements',
            'diagrams.index' => 'diagrams',
            'change_requests.index' => 'change_requests',
            'traceability.index' => 'traceability',
            'acceptance-plan.index' => 'acceptance_plan',
            'strategic_baselines.for-project' => 'strategic_baseline',
            default => null,
        };

        $fallbackIcon = $childDef['icon'] ?? 'element-11';
        $icon = $surfaceKey !== null
            ? entity_icon($surfaceKey, is_string($fallbackIcon) ? $fallbackIcon : 'element-11')
            : $fallbackIcon;

        $leaf = [
            'label' => $childDef['label'] ?? $route,
            'route' => $route,
            'icon' => $icon,
            'icon_v8' => $childDef['icon_v8'] ?? $icon,
        ];

        $projectParam = $childDef['route_project_param'] ?? null;
        if (is_string($projectParam) && $projectParam !== '' && $project !== null) {
            $leaf['route_params'] = [$projectParam => $project->id];
        }

        return $leaf;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function folderTemplates(): array
    {
        $folders = config('navigation.hierarchy.project_folders', []);
        if (! is_array($folders) || $folders === []) {
            return [];
        }

        return array_values(array_filter($folders, fn ($folder) => is_array($folder)));
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
