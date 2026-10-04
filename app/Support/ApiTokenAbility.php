<?php

namespace App\Support;

/**
 * Abilities a personal API token can carry.
 *
 * A token never grants more than its owner's role allows: these only narrow it.
 *  - read:  GET / HEAD / OPTIONS requests
 *  - write: everything else (create, update, delete)
 */
class ApiTokenAbility
{
    public const READ = 'read';

    public const WRITE = 'write';

    /** Lifetimes (days) offered when creating a token. */
    public const EXPIRY_DAYS = [30, 90, 180, 365];

    public const DEFAULT_EXPIRY_DAYS = 90;

    public static function forMethod(string $httpMethod): string
    {
        return in_array(strtoupper($httpMethod), ['GET', 'HEAD', 'OPTIONS'], true)
            ? self::READ
            : self::WRITE;
    }
}
