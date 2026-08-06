<?php

namespace App\Support;

/**
 * Business Need classification: problem vs opportunity (BABOK current-state).
 */
final class NeedType
{
    public const PROBLEM = 'problem';

    public const OPPORTUNITY = 'opportunity';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::PROBLEM,
            self::OPPORTUNITY,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::PROBLEM => __('ui.need_type_problem'),
            self::OPPORTUNITY => __('ui.need_type_opportunity'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
