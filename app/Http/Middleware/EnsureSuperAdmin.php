<?php

namespace App\Http\Middleware;

use App\Support\EntityAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! EntityAccess::isSuperAdmin($request->user())) {
            abort(403);
        }

        return $next($request);
    }
}
