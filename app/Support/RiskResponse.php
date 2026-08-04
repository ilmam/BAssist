<?php

namespace App\Support;

/**
 * Risk treatment strategies (BABOK §6.3 recommendation / response).
 */
final class RiskResponse
{
    public const MITIGATE = 'mitigate';

    public const AVOID = 'avoid';

    public const TRANSFER = 'transfer';

    public const ACCEPT = 'accept';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::MITIGATE,
            self::AVOID,
            self::TRANSFER,
            self::ACCEPT,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::MITIGATE => __('ui.risk_response_mitigate'),
            self::AVOID => __('ui.risk_response_avoid'),
            self::TRANSFER => __('ui.risk_response_transfer'),
            self::ACCEPT => __('ui.risk_response_accept'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
