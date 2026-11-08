<?php

namespace App\Support;

/**
 * Lifecycle status for a Brief (the ask, as received).
 *
 * received → under review → approved | declined | on hold.
 * Declined and on hold are valid end states and need a reason.
 */
final class BriefStatus
{
    public const RECEIVED = 'received';

    public const UNDER_REVIEW = 'under_review';

    public const APPROVED = 'approved';

    public const DECLINED = 'declined';

    public const ON_HOLD = 'on_hold';

    /** @return list<string> */
    public static function values(): array
    {
        return [
            self::RECEIVED,
            self::UNDER_REVIEW,
            self::APPROVED,
            self::DECLINED,
            self::ON_HOLD,
        ];
    }

    public static function default(): string
    {
        return self::RECEIVED;
    }

    /** Statuses that must carry a reason. */
    public static function needsReason(string $code): bool
    {
        return in_array($code, [self::DECLINED, self::ON_HOLD], true);
    }

    /** @return array<string, string> */
    public static function selectOptions(): array
    {
        return [
            self::RECEIVED => __('ui.brief_status_received'),
            self::UNDER_REVIEW => __('ui.brief_status_under_review'),
            self::APPROVED => __('ui.brief_status_approved'),
            self::DECLINED => __('ui.brief_status_declined'),
            self::ON_HOLD => __('ui.brief_status_on_hold'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
