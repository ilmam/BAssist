<?php

namespace App\Support;

/**
 * Risk scan categories (BABOK §6.3 identification).
 */
final class RiskCategory
{
    public const TECHNICAL = 'technical';

    public const ORGANIZATIONAL = 'organizational';

    public const SCHEDULE_RESOURCE = 'schedule_resource';

    public const EXTERNAL_REGULATORY = 'external_regulatory';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::TECHNICAL,
            self::ORGANIZATIONAL,
            self::SCHEDULE_RESOURCE,
            self::EXTERNAL_REGULATORY,
        ];
    }

    public static function default(): string
    {
        return self::TECHNICAL;
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::TECHNICAL => __('ui.risk_category_technical'),
            self::ORGANIZATIONAL => __('ui.risk_category_organizational'),
            self::SCHEDULE_RESOURCE => __('ui.risk_category_schedule_resource'),
            self::EXTERNAL_REGULATORY => __('ui.risk_category_external_regulatory'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
