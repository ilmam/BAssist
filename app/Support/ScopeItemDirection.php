<?php

namespace App\Support;

/**
 * In-scope vs out-of-scope direction for ScopeItem.
 */
final class ScopeItemDirection
{
    public const IN = 'in';

    public const OUT = 'out';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::IN,
            self::OUT,
        ];
    }

    public static function default(): string
    {
        return self::IN;
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::IN => __('ui.scope_item_direction_in'),
            self::OUT => __('ui.scope_item_direction_out'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
