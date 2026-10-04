<?php

use App\Mcp\Servers\BAssistServer;
use Laravel\Mcp\Facades\Mcp;

// The MCP endpoint for AI assistants (docs/mcp.md). Signed in with a personal
// API token; each tool checks role permissions, and write tools also require
// the token's "write" ability. mcp.channel labels resulting history entries.
Mcp::web('/mcp', BAssistServer::class)
    ->middleware(['auth:sanctum', 'mcp.channel', 'throttle:120,1']);
