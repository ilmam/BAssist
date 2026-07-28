<?php

namespace App\Support;

/**
 * Lifecycle status for Constraint guardrails.
 */
final class ConstraintStatus
{
    public const ACTIVE = 'active';

    public const WAIVED = 'waived';

    public const RETIRED = 'retired';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::ACTIVE,
            self::WAIVED,
            self::RETIRED,
        ];
    }

    public static function default(): string
    {
        return self::ACTIVE;
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::ACTIVE => __('ui.constraint_status_active'),
            self::WAIVED => __('ui.constraint_status_waived'),
            self::RETIRED => __('ui.constraint_status_retired'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
