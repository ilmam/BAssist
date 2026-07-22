<?php

namespace App\Helpers;

use ReflectionClass;
use ReflectionProperty;

/**
 * Reflects public properties for a given App\Attributes\* attribute.
 *
 * Callers pass short names such as 'Value', 'Form', 'InList', 'Hide', 'Relation'.
 * Legacy long names (e.g. ValuePropertyAttribute) are mapped to the short names.
 */
class AttributeHelper
{
    /** @var array<string, string> */
    private const LEGACY_ALIASES = [
        'FormFieldAttribute' => 'Form',
        'ValuePropertyAttribute' => 'Value',
        'ListPropertyAttribute' => 'InList',
        'HidePropertyAttribute' => 'Hide',
        'RelationAttribute' => 'Relation',
        // QuickCreateAttribute has no direct class successor.
    ];

    /**
     * @param  object|class-string  $object
     * @return array<string, array<int, mixed>>|list<string>
     */
    public static function getPropertyAttributes(
        object|string $object,
        string $attributeName,
        bool $includeArguments = true,
    ): array {
        $attributeName = self::LEGACY_ALIASES[$attributeName] ?? $attributeName;
        $attributeFqcn = 'App\\Attributes\\'.$attributeName;

        $reflect = new ReflectionClass($object);
        $props = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        $properties = [];
        foreach ($props as $prop) {
            $attributes = $prop->getAttributes($attributeFqcn);
            foreach ($attributes as $attribute) {
                if ($attribute->getName() === $attributeFqcn) {
                    if ($includeArguments) {
                        $properties[$prop->name] = $attribute->getArguments();
                    } else {
                        $properties[] = $prop->name;
                    }
                }
            }
        }

        return $properties;
    }
}
