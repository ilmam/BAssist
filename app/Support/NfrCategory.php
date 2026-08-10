<?php

namespace App\Support;

/**
 * Non-functional / quality-of-service category for solution NFRs.
 */
final class NfrCategory
{
    public const PERFORMANCE = 'performance';

    public const SECURITY = 'security';

    public const AVAILABILITY = 'availability';

    public const RELIABILITY = 'reliability';

    public const USABILITY = 'usability';

    public const SCALABILITY = 'scalability';

    public const MAINTAINABILITY = 'maintainability';

    public const ACCESSIBILITY = 'accessibility';

    public const COMPLIANCE = 'compliance';

    public const OTHER = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::PERFORMANCE,
            self::SECURITY,
            self::AVAILABILITY,
            self::RELIABILITY,
            self::USABILITY,
            self::SCALABILITY,
            self::MAINTAINABILITY,
            self::ACCESSIBILITY,
            self::COMPLIANCE,
            self::OTHER,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::PERFORMANCE => __('ui.nfr_category_performance'),
            self::SECURITY => __('ui.nfr_category_security'),
            self::AVAILABILITY => __('ui.nfr_category_availability'),
            self::RELIABILITY => __('ui.nfr_category_reliability'),
            self::USABILITY => __('ui.nfr_category_usability'),
            self::SCALABILITY => __('ui.nfr_category_scalability'),
            self::MAINTAINABILITY => __('ui.nfr_category_maintainability'),
            self::ACCESSIBILITY => __('ui.nfr_category_accessibility'),
            self::COMPLIANCE => __('ui.nfr_category_compliance'),
            self::OTHER => __('ui.nfr_category_other'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
