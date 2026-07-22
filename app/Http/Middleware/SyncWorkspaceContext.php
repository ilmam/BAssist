<?php

namespace App\Http\Middleware;

use App\Support\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Persist workspace_id from list/deep-link URLs into the session, and honor clear_workspace.
 */
class SyncWorkspaceContext
{
    public function __construct(protected WorkspaceContext $workspaceContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->boolean('clear_workspace')) {
            $this->workspaceContext->clear();

            if ($request->isMethod('GET') && ! $request->ajax() && ! $request->wantsJson()) {
                $query = $request->query();
                unset($query['clear_workspace'], $query['workspace_id']);

                $url = $request->url();
                if ($query !== []) {
                    $url .= '?'.http_build_query($query);
                }

                return redirect()->to($url);
            }

            return $next($request);
        }

        if ($request->filled('workspace_id') && is_numeric($request->input('workspace_id'))) {
            $this->workspaceContext->set((int) $request->input('workspace_id'));
        }

        return $next($request);
    }
}
