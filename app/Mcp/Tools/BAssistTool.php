<?php

namespace App\Mcp\Tools;

use App\Support\ApiTokenAbility;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Base for every BAssist MCP tool.
 *
 * A tool holds no business logic: it reads its arguments, calls a service
 * (ProjectInsightsService or EntityRecordService) and returns the result.
 * Authorization, validation and tenant isolation happen inside those services,
 * exactly as they do for the web UI and the JSON API. See docs/mcp.md.
 */
abstract class BAssistTool extends Tool
{
    /**
     * Run the tool body and turn "not found" into a message the assistant can
     * act on. Validation and permission failures are already reported by the
     * MCP package; anything else is reported and hidden unless APP_DEBUG is on.
     *
     * @param  Closure(): array<string, mixed>  $callback
     */
    protected function respond(Closure $callback): Response|ResponseFactory
    {
        try {
            return Response::structured($callback());
        } catch (ModelNotFoundException) {
            return Response::error('Not found. The record does not exist or is not in your organisation.');
        } catch (HttpExceptionInterface $e) {
            if ($e->getStatusCode() === 404) {
                return Response::error($e->getMessage() !== '' ? $e->getMessage() : 'Not found.');
            }

            throw $e;
        }
    }

    /**
     * A read-only API token may call read tools only. Browser sessions and
     * tests acting as a user carry no token and are limited by role alone.
     *
     * @throws AuthorizationException
     */
    protected function requireWrite(Request $request): void
    {
        $user = $request->user();

        if ($user !== null
            && method_exists($user, 'currentAccessToken')
            && $user->currentAccessToken() !== null
            && ! $user->tokenCan(ApiTokenAbility::WRITE)) {
            throw new AuthorizationException('This API token is read only. Create a token with write access to change records.');
        }
    }
}
