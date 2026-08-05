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

    /**
     * Statuses selectable on the CR form (approval uses a dedicated confirm flow).
     *
     * @return list<string>
     */
    public static function formValues(): array
    {
        return [
            self::DRAFT,
            self::UNDER_REVIEW,
            self::REJECTED,
            self::IMPLEMENTED,
        ];
    }

    public static function default(): string
    {
        return self::DRAFT;
    }

    /**
     * Statuses that require a Stakeholder Need anchor.
     *
     * @return list<string>
     */
    public static function requiresStakeholderNeed(): array
    {
        return [
            self::UNDER_REVIEW,
            self::APPROVED,
            self::IMPLEMENTED,
        ];
    }

    /**
     * @deprecated Use requiresStakeholderNeed()
     *
     * @return list<string>
     */
    public static function requiresAffected(): array
    {
        return self::requiresStakeholderNeed();
    }

    /**
     * @deprecated Use requiresStakeholderNeed()
     *
     * @return list<string>
     */
    public static function requiresUpstream(): array
    {
        return self::requiresStakeholderNeed();
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        $options = [];
        foreach (self::formValues() as $code) {
            $options[$code] = self::label($code);
        }

        // Keep approved visible when already approved (readonly display via form value).
        $options[self::APPROVED] = __('ui.change_request_status_approved');

        return $options;
    }

    public static function label(string $code): string
    {
        return match ($code) {
            self::DRAFT => __('ui.change_request_status_draft'),
            self::UNDER_REVIEW => __('ui.change_request_status_under_review'),
            self::APPROVED => __('ui.change_request_status_approved'),
            self::REJECTED => __('ui.change_request_status_rejected'),
            self::IMPLEMENTED => __('ui.change_request_status_implemented'),
            default => $code,
        };
    }
}
