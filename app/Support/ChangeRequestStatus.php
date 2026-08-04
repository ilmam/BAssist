<?php

namespace App\Support;

/**
 * Lifecycle status for Change Requests.
 */
final class ChangeRequestStatus
{
    public const DRAFT = 'draft';

    public const UNDER_REVIEW = 'under_review';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const IMPLEMENTED = 'implemented';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::DRAFT,
            self::UNDER_REVIEW,
            self::APPROVED,
            self::REJECTED,
            self::IMPLEMENTED,
        ];
    }

    public static function default(): string
    {
        return self::DRAFT;
    }

    /**
     * Statuses that require at least one affected requirement link.
     *
     * @return list<string>
     */
    public static function requiresAffected(): array
    {
        return [
            self::UNDER_REVIEW,
            self::APPROVED,
            self::IMPLEMENTED,
        ];
    }

    /**
     * @deprecated Use requiresAffected()
     *
     * @return list<string>
     */
    public static function requiresUpstream(): array
    {
        return self::requiresAffected();
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::DRAFT => __('ui.change_request_status_draft'),
            self::UNDER_REVIEW => __('ui.change_request_status_under_review'),
            self::APPROVED => __('ui.change_request_status_approved'),
            self::REJECTED => __('ui.change_request_status_rejected'),
            self::IMPLEMENTED => __('ui.change_request_status_implemented'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
