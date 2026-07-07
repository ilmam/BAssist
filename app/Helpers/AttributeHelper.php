<?php
namespace App\Helpers;

use App\Support\DtoMetadata;

class AttributeHelper
{
    public static function getPropertyAttributes($object, $attributeName, $includeArguments = true)
    {
        if ($attributeName === 'FormFieldAttribute') {
            $fields = DtoMetadata::for($object)->formFields();

            if ($includeArguments) {
                return $fields;
            }

            return array_keys($fields);
        }

        throw new \InvalidArgumentException(
            "AttributeHelper only delegates FormFieldAttribute to DtoMetadata. Use DtoMetadata::for() for [{$attributeName}]."
        );
    }
}
