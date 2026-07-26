<?php

namespace App\Http\Middleware;

use App\Support\ProjectContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Persist project_id from list/deep-link URLs into the session, and honor clear_project.
 */
class SyncProjectContext
{
    public function __construct(protected ProjectContext $projectContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->boolean('clear_project')) {
            $this->projectContext->clear();

            if ($request->isMethod('GET') && ! $request->ajax() && ! $request->wantsJson()) {
                $query = $request->query();
                unset($query['clear_project'], $query['project_id']);

                $url = $request->url();
                if ($query !== []) {
                    $url .= '?'.http_build_query($query);
                }

                return redirect()->to($url);
            }

            return $next($request);
        }

        if ($request->filled('project_id') && is_numeric($request->input('project_id'))) {
            $this->projectContext->set((int) $request->input('project_id'));
        }

        return $next($request);
    }
}
