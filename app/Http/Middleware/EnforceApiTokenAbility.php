<?php

namespace App\Http\Middleware;

use App\Support\ApiTokenAbility;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limits personal API tokens to the abilities they were issued with:
 * a read-only token may not create, update or delete.
 *
 * Browser sessions (the app's own datatables and forms) are not tokens and
 * pass through untouched; role permissions still apply to every request.
 */
class EnforceApiTokenAbility
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null
            && method_exists($user, 'currentAccessToken')
            && $user->currentAccessToken() !== null
            && ! $user->tokenCan(ApiTokenAbility::forMethod($request->method()))) {
            abort(403, 'This API token does not have the "'.ApiTokenAbility::forMethod($request->method()).'" ability.');
        }

        return $next($request);
    }
}
