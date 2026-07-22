<?php

namespace App\Support;

use App\Models\Status;
use Illuminate\Support\Facades\Cache;

final class EntityStatus
{
    public const DRAFT = 'draft';
    public const AGREED = 'agreed';
    public const DEPRECATED = 'deprecated';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::DRAFT,
            self::AGREED,
            self::DEPRECATED,
        ];
    }

    public static function default(): string
    {
        return self::DRAFT;
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

    public static function is(string $code, ?int $id): bool
    {
        return $id !== null && self::id($code) === $id;
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
            return Status::query()
                ->pluck('id', 'code')
                ->map(fn ($id) => (int) $id)
                ->all();
        });
    }

    protected static function cacheKey(): string
    {
        return 'entity_status.code_to_id';
    }
}
