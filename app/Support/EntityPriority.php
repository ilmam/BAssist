<?php

namespace App\Support;

use App\Models\Priority;
use Illuminate\Support\Facades\Cache;

/**
 * Canonical MoSCoW priority codes used by the need spine.
 *
 * Seeded rows for these codes are system-locked (`is_system = true`) via
 * StatusPrioritySeeder. Custom priorities may exist with other codes and are never system.
 */
final class EntityPriority
{
    public const MUST = 'must';

    public const SHOULD = 'should';

    public const COULD = 'could';

    public const WONT = 'wont';

    /** @deprecated Use MUST */
    public const HIGH = self::MUST;

    /** @deprecated Use SHOULD */
    public const MEDIUM = self::SHOULD;

    /** @deprecated Use COULD */
    public const LOW = self::COULD;

    /**
     * System allowlist codes (also marked is_system when seeded).
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::MUST,
            self::SHOULD,
            self::COULD,
            self::WONT,
        ];
    }

    public static function default(): string
    {
        return self::SHOULD;
    }

    public static function defaultId(): ?int
    {
        return self::id(self::default());
    }

    public static function id(string $code): ?int
    {
        $map = self::codeToIdMap();

        return $map[$code] ?? null;
    }

    public static function code(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $map = array_flip(self::codeToIdMap());

        return $map[$id] ?? null;
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::cacheKey());
    }

    /**
     * @return array<string, int>
     */
    protected static function codeToIdMap(): array
    {
        return Cache::rememberForever(self::cacheKey(), function () {
            return Priority::query()
                ->pluck('id', 'code')
                ->map(fn ($id) => (int) $id)
                ->all();
        });
    }

    protected static function cacheKey(): string
    {
        return 'entity_priority.code_to_id';
    }
}
