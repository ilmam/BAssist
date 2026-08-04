<?php

namespace App\Support;

/**
 * Impact severity scale for Risk Level = Likelihood × Impact (1–3).
 */
final class RiskImpact
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

    public static function weight(string $code): int
    {
        return match ($code) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            default => 0,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::LOW => __('ui.risk_impact_low'),
            self::MEDIUM => __('ui.risk_impact_medium'),
            self::HIGH => __('ui.risk_impact_high'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
