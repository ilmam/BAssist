<?php

namespace App\Support;

use Laravel\Sanctum\PersonalAccessToken;

/**
 * How the current request reached the application, for the record history:
 *
 *  - null  the web UI (browser session)
 *  - api   a personal API token calling the JSON API
 *  - mcp   an AI assistant calling the MCP endpoint
 *
 * Bound as a scoped singleton (AppServiceProvider), so it resets per request.
 */
class RequestChannel
{
    public const API = 'api';

    public const MCP = 'mcp';

    protected ?string $explicit = null;

    public function mark(string $channel): void
    {
        $this->explicit = $channel;
    }

    public function current(): ?string
    {
        if ($this->explicit !== null) {
            return $this->explicit;
        }

        $user = auth()->user();

        if ($user !== null
            && method_exists($user, 'currentAccessToken')
            && $user->currentAccessToken() instanceof PersonalAccessToken) {
            return self::API;
        }

        return null;
    }
}
