<?php

namespace App\Support;

/**
 * Likelihood scale for Risk Level = Likelihood × Impact (1–3).
 */
final class RiskLikelihood
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
            self::LOW => __('ui.risk_likelihood_low'),
            self::MEDIUM => __('ui.risk_likelihood_medium'),
            self::HIGH => __('ui.risk_likelihood_high'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
