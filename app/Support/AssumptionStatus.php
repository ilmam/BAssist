<?php

namespace App\Support;

/**
 * Lifecycle status for Assumption guardrails.
 */
final class AssumptionStatus
{
    public const OPEN = 'open';

    public const VALIDATED = 'validated';

    public const INVALIDATED = 'invalidated';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::OPEN,
            self::VALIDATED,
            self::INVALIDATED,
        ];
    }

    public static function default(): string
    {
        return self::OPEN;
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::OPEN => __('ui.assumption_status_open'),
            self::VALIDATED => __('ui.assumption_status_validated'),
            self::INVALIDATED => __('ui.assumption_status_invalidated'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
