<?php

namespace App\Support;

/**
 * Lifecycle status for StrategicBaseline (project strategy document).
 *
 * Dedicated string codes — not shared EntityStatus (draft/agreed/deprecated) —
 * so Need Spine dropdowns stay untouched while beginners get Draft / In review / Approved.
 */
final class StrategicBaselineStatus
{
    public const DRAFT = 'draft';

    public const IN_REVIEW = 'in_review';

    public const APPROVED = 'approved';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::DRAFT,
            self::IN_REVIEW,
            self::APPROVED,
        ];
    }

    public static function default(): string
    {
        return self::DRAFT;
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::DRAFT => __('ui.strategic_baseline_status_draft'),
            self::IN_REVIEW => __('ui.strategic_baseline_status_in_review'),
            self::APPROVED => __('ui.strategic_baseline_status_approved'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
