<?php

namespace App\Support;

/**
 * Primary subject type for a Change Request (what is being changed).
 */
final class ChangeRequestAffectedType
{
    public const BUSINESS_OBJECTIVE = 'business_objective';

    public const BUSINESS_NEED = 'business_need';

    public const STAKEHOLDER_NEED = 'stakeholder_need';

    public const FEATURE = 'feature';

    public const FUNCTIONAL_REQUIREMENT = 'functional_requirement';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::BUSINESS_OBJECTIVE,
            self::BUSINESS_NEED,
            self::STAKEHOLDER_NEED,
            self::FEATURE,
            self::FUNCTIONAL_REQUIREMENT,
        ];
    }

    /**
     * @return array<string, class-string>
     */
    public static function modelMap(): array
    {
        return [
            self::BUSINESS_OBJECTIVE => \App\Models\BusinessObjective::class,
            self::BUSINESS_NEED => \App\Models\BusinessNeed::class,
            self::STAKEHOLDER_NEED => \App\Models\StakeholderNeed::class,
            self::FEATURE => \App\Models\Feature::class,
            self::FUNCTIONAL_REQUIREMENT => \App\Models\FunctionalRequirement::class,
        ];
    }

    public static function modelClass(string $type): ?string
    {
        return self::modelMap()[$type] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return [
            self::BUSINESS_OBJECTIVE => __('ui.business_objective'),
            self::BUSINESS_NEED => __('ui.business_need'),
            self::STAKEHOLDER_NEED => __('ui.stakeholder_need'),
            self::FEATURE => __('ui.feature'),
            self::FUNCTIONAL_REQUIREMENT => __('ui.functional_requirement'),
        ];
    }

    public static function label(string $code): string
    {
        return self::selectOptions()[$code] ?? $code;
    }
}
