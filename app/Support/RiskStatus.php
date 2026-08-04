<?php

namespace App\Support;

/**
 * Lifecycle status for Risk Assessment (BABOK §6.3).
 */
final class RiskStatus
{
    public const OPEN = 'open';

    public const MITIGATED = 'mitigated';

    public const REALIZED = 'realized';

    public const CLOSED = 'closed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::OPEN,
            self::MITIGATED,
            self::REALIZED,
            self::CLOSED,
        ];
    }

    public static function default(): string
    {
        return self::OPEN;
    }

    /**
     * Statuses that still need monitoring / treatment coverage.
     *
     * @return list<string>
     */
    public static function active(): array
    {
        return [
            self::OPEN,
            self::REALIZED,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::OPEN => __('ui.risk_status_open'),
            self::MITIGATED => __('ui.risk_status_mitigated'),
            self::REALIZED => __('ui.risk_status_realized'),
            self::CLOSED => __('ui.risk_status_closed'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
