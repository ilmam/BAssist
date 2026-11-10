<?php

namespace App\Support;

/**
 * Stakeholder types, listed outermost to innermost ring of the BABOK stakeholder
 * onion diagram (§10.43): external, organization, affected unit, solution delivery.
 */
final class StakeholderType
{
    public const CUSTOMER = 'customer';

    public const SUPPLIER = 'supplier';

    public const REGULATOR = 'regulator';

    public const EXECUTIVE = 'executive';

    public const SPONSOR = 'sponsor';

    public const DOMAIN_EXPERT = 'domain_sme';

    public const END_USER = 'end_user';

    public const OPERATIONAL_SUPPORT = 'operational_support';

    public const IMPLEMENTATION_EXPERT = 'implementation_sme';

    public const BUSINESS_ANALYST = 'business_analyst';

    public const PROJECT_MANAGER = 'project_manager';

    public const TESTER = 'tester';

    public const OTHER = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::selectOptions());
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::CUSTOMER => __('ui.stakeholder_type_customer'),
            self::SUPPLIER => __('ui.stakeholder_type_supplier'),
            self::REGULATOR => __('ui.stakeholder_type_regulator'),
            self::EXECUTIVE => __('ui.stakeholder_type_executive'),
            self::SPONSOR => __('ui.stakeholder_type_sponsor'),
            self::DOMAIN_EXPERT => __('ui.stakeholder_type_domain_sme'),
            self::END_USER => __('ui.stakeholder_type_end_user'),
            self::OPERATIONAL_SUPPORT => __('ui.stakeholder_type_operational_support'),
            self::IMPLEMENTATION_EXPERT => __('ui.stakeholder_type_implementation_sme'),
            self::BUSINESS_ANALYST => __('ui.stakeholder_type_business_analyst'),
            self::PROJECT_MANAGER => __('ui.stakeholder_type_project_manager'),
            self::TESTER => __('ui.stakeholder_type_tester'),
            self::OTHER => __('ui.stakeholder_type_other'),
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
