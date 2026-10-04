<?php

namespace App\Http\Middleware;

use App\Support\RequestChannel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks the request as coming through the MCP endpoint, so changes made by an
 * AI assistant are labelled as such in each record's history.
 */
class MarkMcpChannel
{
    public function __construct(protected RequestChannel $channel)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->channel->mark(RequestChannel::MCP);

        return $next($request);
    }
}
