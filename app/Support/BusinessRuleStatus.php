<?php

namespace App\Support;

/**
 * Lifecycle status for BusinessRule guardrails.
 */
final class BusinessRuleStatus
{
    public const DRAFT = 'draft';

    public const ACTIVE = 'active';

    public const RETIRED = 'retired';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::DRAFT,
            self::ACTIVE,
            self::RETIRED,
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
            self::DRAFT => __('ui.business_rule_status_draft'),
            self::ACTIVE => __('ui.business_rule_status_active'),
            self::RETIRED => __('ui.business_rule_status_retired'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
