<?php

namespace App\Support;

/**
 * Likelihood × Impact scoring (3×3) for Risk Assessment.
 *
 * Critical scores (9) are highlighted in the UI and Package 1; they are not
 * blocked by form validation.
 */
final class RiskScore
{
    public const BAND_LOW = 'low';

    public const BAND_MEDIUM = 'medium';

    public const BAND_HIGH = 'high';

    public const BAND_CRITICAL = 'critical';

    /** High band starts at this score (3×2 / 2×3). Highlighting uses Critical only. */
    public const HIGH_AT = 6;

    /** Critical band (3×3). */
    public const CRITICAL_AT = 9;

    public static function calculate(string $likelihood, string $impact): int
    {
        return RiskLikelihood::weight($likelihood) * RiskImpact::weight($impact);
    }

    public static function band(int $score): string
    {
        return match (true) {
            $score >= self::CRITICAL_AT => self::BAND_CRITICAL,
            $score >= self::HIGH_AT => self::BAND_HIGH,
            $score >= 3 => self::BAND_MEDIUM,
            default => self::BAND_LOW,
        };
    }

    public static function isCritical(int $score): bool
    {
        return $score >= self::CRITICAL_AT;
    }

    public static function bandLabel(string $band): string
    {
        return match ($band) {
            self::BAND_LOW => __('ui.risk_score_band_low'),
            self::BAND_MEDIUM => __('ui.risk_score_band_medium'),
            self::BAND_HIGH => __('ui.risk_score_band_high'),
            self::BAND_CRITICAL => __('ui.risk_score_band_critical'),
            default => $band,
        };
    }

    public static function display(int $score): string
    {
        return $score.' — '.self::bandLabel(self::band($score));
    }
}
