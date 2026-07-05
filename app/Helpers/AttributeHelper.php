<?php
namespace App\Helpers;

use App\Attributes\ValuePropertyAttribute;

class AttributeHelper
{
    public static function getPropertyAttributes($object, $attributeName, $includeArguments=true)
    {
        $reflect = new \ReflectionClass($object);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

        $properties = [];
        foreach($props as $prop) {
            $attributes = $prop->getAttributes("App\Attributes\\$attributeName");
            foreach ($attributes as $attribute) {
                if ($attribute->getName() == "App\Attributes\\$attributeName") {
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
