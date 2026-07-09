<?php

namespace App\Http\Middleware;

use App\Support\EntityAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeEntityAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $resolved = EntityAccess::resolveFromRoute($request->route());

        if ($resolved === null) {
            return $next($request);
        }

        EntityAccess::authorize($request->user(), $resolved['entity'], $resolved['ability']);

        return $next($request);
    }
}
