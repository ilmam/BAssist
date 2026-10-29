<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * A counter bumped whenever any tracked record changes. Cached read models (the home
 * page) put it in their key, so a change is visible at once and the old entry just expires.
 */
class DataVersion
{
    private const KEY = 'data.version';

    public static function current(): int
    {
        return (int) Cache::get(self::KEY, 0);
    }

    public static function bump(): void
    {
        Cache::forever(self::KEY, self::current() + 1);
    }
}
