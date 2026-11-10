<?php

namespace App\Support;

/**
 * Responsibility (RACI) matrix roles (BABOK §10.43.3).
 */
final class RaciRole
{
    public const RESPONSIBLE = 'R';

    public const ACCOUNTABLE = 'A';

    public const CONSULTED = 'C';

    public const INFORMED = 'I';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [self::RESPONSIBLE, self::ACCOUNTABLE, self::CONSULTED, self::INFORMED];
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::RESPONSIBLE => __('ui.raci_role_r'),
            self::ACCOUNTABLE => __('ui.raci_role_a'),
            self::CONSULTED => __('ui.raci_role_c'),
            self::INFORMED => __('ui.raci_role_i'),
        ];
    }

    public static function label(?string $code): string
    {
        return $code === null || $code === '' ? '—' : (self::selectOptions()[$code] ?? $code);
    }
}
