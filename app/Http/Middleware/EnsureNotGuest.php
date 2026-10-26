<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps people invited to a project (guests) out of pages that belong to full
 * members, such as personal API tokens.
 */
class EnsureNotGuest
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->is_guest, 403);

        return $next($request);
    }
}
