<?php
namespace App\Helpers;

use App\Support\DtoMetadata;

class AttributeHelper
{
    public static function getPropertyAttributes($object, $attributeName, $includeArguments = true)
    {
        if ($attributeName === 'Form') {
            $fields = DtoMetadata::for($object)->formFields();

            if ($includeArguments) {
                return $fields;
            }

            return array_keys($fields);
        }

        throw new \InvalidArgumentException(
            "AttributeHelper only delegates Form to DtoMetadata. Use DtoMetadata::for() for [{$attributeName}]."
        );
    }
}
