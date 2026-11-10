<?php

namespace App\Support;

/**
 * Low / Medium / High scale for a stakeholder's influence and interest
 * (BABOK §10.43 Stakeholder List, Map, or Personas — stakeholder matrix).
 */
final class StakeholderLevel
{
    public const LOW = 'low';

    public const MEDIUM = 'medium';

    public const HIGH = 'high';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [self::LOW, self::MEDIUM, self::HIGH];
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::LOW => __('ui.stakeholder_level_low'),
            self::MEDIUM => __('ui.stakeholder_level_medium'),
            self::HIGH => __('ui.stakeholder_level_high'),
        ];
    }

    public static function label(?string $code): string
    {
        if ($code === null || $code === '') {
            return '—';
        }

        return self::selectOptions()[$code] ?? $code;
    }
}
