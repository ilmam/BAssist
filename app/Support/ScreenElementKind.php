<?php

namespace App\Support;

use App\Services\SaltScreenAssembler;

/**
 * Widget kind of a screen element row; each maps to a Salt widget in SaltScreenAssembler.
 */
final class ScreenElementKind
{
    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return SaltScreenAssembler::KINDS;
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return array_combine(self::values(), array_map(
            fn (string $kind) => $kind === 'tablerow' ? 'Table row' : ucfirst($kind),
            self::values(),
        ));
    }

    public static function isContainer(string $code): bool
    {
        return in_array($code, SaltScreenAssembler::CONTAINER_KINDS, true);
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
