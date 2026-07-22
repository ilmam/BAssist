<?php

namespace App\Support;

use App\Models\Priority;
use Illuminate\Support\Facades\Cache;

final class EntityPriority
{
    public const HIGH = 'high';
    public const MEDIUM = 'medium';
    public const LOW = 'low';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::HIGH,
            self::MEDIUM,
            self::LOW,
        ];
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
