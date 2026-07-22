<?php

namespace App\Support;

use App\Models\Workspace;
use App\Repositories\BaseRepository;

/**
 * Session-sticky workspace scope for list navigation.
 * Tenant stays implicit from the authenticated user; workspace persists across bare nav links.
 */
class WorkspaceContext
{
    public const SESSION_KEY = 'list_context.workspace_id';

    public function id(): ?int
    {
        $raw = session(self::SESSION_KEY);

        if ($raw === null || $raw === '') {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }

    public function set(int $workspaceId): void
    {
        if ($workspaceId <= 0 || ! $this->belongsToCurrentTenant($workspaceId)) {
            $this->clear();

            return;
        }

        session([self::SESSION_KEY => $workspaceId]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Inject the sticky workspace into list filters when the entity supports it
     * and the request did not already supply workspace_id.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function mergeIntoFilters(array $filters, BaseRepository $repository): array
    {
        if (! $repository->usesWorkspaceListScope()) {
            return $filters;
        }

        if (array_key_exists('workspace_id', $filters) && $filters['workspace_id'] !== null && $filters['workspace_id'] !== '') {
            return $filters;
        }

        $id = $this->id();
        if ($id === null) {
            return $filters;
        }

        // Drop stale session ids that no longer belong to this tenant.
        if (! $this->belongsToCurrentTenant($id)) {
            $this->clear();

            return $filters;
        }

        $filters['workspace_id'] = $id;

        return $filters;
    }

    protected function belongsToCurrentTenant(int $workspaceId): bool
    {
        $tenantId = auth()->user()?->tenant_id;
        if ($tenantId === null) {
            return false;
        }

        return Workspace::query()
            ->whereKey($workspaceId)
            ->where('tenant_id', $tenantId)
            ->exists();
    }
}
