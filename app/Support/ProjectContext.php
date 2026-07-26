<?php

namespace App\Support;

use App\Models\Project;
use App\Repositories\BaseRepository;

/**
 * Session-sticky project scope for list navigation.
 * Setting a project also sticks its parent workspace.
 */
class ProjectContext
{
    public const SESSION_KEY = 'list_context.project_id';

    public function __construct(protected WorkspaceContext $workspaceContext)
    {
    }

    public function id(): ?int
    {
        $raw = session(self::SESSION_KEY);

        if ($raw === null || $raw === '') {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }

    public function set(int $projectId): void
    {
        if ($projectId <= 0) {
            $this->clear();

            return;
        }

        $project = $this->projectForCurrentTenant($projectId);
        if ($project === null) {
            $this->clear();

            return;
        }

        session([self::SESSION_KEY => $projectId]);
        $this->workspaceContext->set((int) $project->workspace_id);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Drop sticky project when it no longer belongs to the sticky workspace.
     */
    public function clearIfNotInWorkspace(?int $workspaceId): void
    {
        $projectId = $this->id();
        if ($projectId === null) {
            return;
        }

        if ($workspaceId === null) {
            $this->clear();

            return;
        }

        $project = $this->projectForCurrentTenant($projectId);
        if ($project === null || (int) $project->workspace_id !== $workspaceId) {
            $this->clear();
        }
    }

    /**
     * Inject the sticky project into list filters when the entity supports it
     * and the request did not already supply project_id.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function mergeIntoFilters(array $filters, BaseRepository $repository): array
    {
        if (! $repository->usesProjectListScope()) {
            return $filters;
        }

        if (array_key_exists('project_id', $filters) && $filters['project_id'] !== null && $filters['project_id'] !== '') {
            return $filters;
        }

        $id = $this->id();
        if ($id === null) {
            return $filters;
        }

        $project = $this->projectForCurrentTenant($id);
        if ($project === null) {
            $this->clear();

            return $filters;
        }

        $filters['project_id'] = $id;

        if (
            (! array_key_exists('workspace_id', $filters) || $filters['workspace_id'] === null || $filters['workspace_id'] === '')
            && $repository->usesWorkspaceListScope()
        ) {
            $filters['workspace_id'] = (int) $project->workspace_id;
        }

        return $filters;
    }

    protected function projectForCurrentTenant(int $projectId): ?Project
    {
        $tenantId = auth()->user()?->tenant_id;
        if ($tenantId === null) {
            return null;
        }

        return Project::query()
            ->whereKey($projectId)
            ->whereHas('workspace', fn ($query) => $query->where('tenant_id', $tenantId))
            ->first(['id', 'workspace_id']);
    }
}
