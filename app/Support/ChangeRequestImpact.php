<?php

namespace App\Support;

/**
 * Coarse impact triage for Change Requests (BABOK §5.4 lightweight).
 */
final class ChangeRequestImpact
{
    public const LOW = 'low';

    public const MEDIUM = 'medium';

    public const HIGH = 'high';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::LOW,
            self::MEDIUM,
            self::HIGH,
        ];
    }

    public static function default(): string
    {
        return self::MEDIUM;
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::LOW => __('ui.change_request_impact_low'),
            self::MEDIUM => __('ui.change_request_impact_medium'),
            self::HIGH => __('ui.change_request_impact_high'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
