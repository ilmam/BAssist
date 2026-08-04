<?php

namespace App\Support;

/**
 * Requirement types a BPD / swimlane process step can satisfy.
 */
final class ProcessStepSatisfyType
{
    public const FEATURE = 'feature';

    public const FUNCTIONAL_REQUIREMENT = 'functional_requirement';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
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
            self::FEATURE => \App\Models\Feature::class,
            self::FUNCTIONAL_REQUIREMENT => \App\Models\FunctionalRequirement::class,
        ];
    }

    public static function modelClass(string $type): ?string
    {
        return self::modelMap()[$type] ?? null;
    }

    public static function isValid(?string $type): bool
    {
        return $type !== null && $type !== '' && in_array($type, self::values(), true);
    }

    /**
     * Encode type+id for a single select value (e.g. feature:12).
     */
    public static function encode(?string $type, int|string|null $id): string
    {
        if (! self::isValid($type) || $id === null || (int) $id < 1) {
            return '';
        }

        return $type.':'.(int) $id;
    }

    /**
     * @return array{type: string|null, id: int|null}
     */
    public static function decode(mixed $value): array
    {
        if (! is_string($value) || $value === '') {
            return ['type' => null, 'id' => null];
        }

        if (! str_contains($value, ':')) {
            return ['type' => null, 'id' => null];
        }

        [$type, $idPart] = explode(':', $value, 2);
        $type = trim($type);
        $id = is_numeric($idPart) ? (int) $idPart : 0;

        if (! self::isValid($type) || $id < 1) {
            return ['type' => null, 'id' => null];
        }

        return ['type' => $type, 'id' => $id];
    }

    public static function label(string $code): string
    {
        return match ($code) {
            self::FEATURE => __('ui.feature'),
            self::FUNCTIONAL_REQUIREMENT => __('ui.functional_requirement'),
            default => $code,
        };
    }
}
