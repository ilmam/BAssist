<?php
namespace App\Helpers;

class DtoHelper
{
    const VALUE_PROPERTY_ATTRIBUTE = "ListPropertyAttribute";//|HidePropertyAttribute|ALWAYS

    /**
     * This method retieves headers or headers and data from dto
     * It supports both objects (with values) and class without values
     * Par: $reflectClass bool : Type of passed object (class, object)
     */
    public static function getFields($reflectionType = 'object', $withPrefix = true, $prefix = '', $reflectedObject = null, $valueProperyAttr = null)
    {
        $reflect = new \ReflectionClass($reflectedObject);//either object or class full name

        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        $fields = array();
        $sep = ".";

        foreach($props as $prop) {
            $fieldName = $prop->getName();
            $fullFieldName = ltrim($prefix.$sep.$fieldName, ".");
            if ($reflectionType == 'class') {
                //if we are reflecting a class send type
                $value = $prop->getType()->getName();

                //chekc the class property's type
                $type = $prop->getType();
                if ($type !== null && $type->isBuiltin()) {
                    //is a primitive type, don't drill down
                    $propClassName = null;
                } elseif ($type != null) {
                    // is an object of class {$prop->getType()->getName()}
                    $propClassName = $prop->getType()->getName();
                } else {
                    //something else??
                    $propClassName = null;
                }
            } else {
                //if we are reflecting an object send value
                //dd($reflectedObject->{$fieldName});
                $value = $reflectedObject->{$fieldName}; //$prop->getValue($reflectedObject);
                $propClassName = null;
            }

            if (is_array($value) || is_object($value) || $propClassName != null) {
                $fields = array_merge($fields, self::getFields($reflectionType, $withPrefix, $fullFieldName, $value, $valueProperyAttr));
            } else {
                $attributes = $prop->getAttributes();
                if ($valueProperyAttr) {
                    $valueProperyAttr = self::VALUE_PROPERTY_ATTRIBUTE;
                }
                foreach ($attributes as $attribute) {
                    if ($attribute->getName() == "App\\Attributes\\".$valueProperyAttr) {
                        $key = $withPrefix ? $fullFieldName : $fieldName;
                        if ($reflectionType == 'class') {
                            $fields[] = $key;
                        } else {
                            $fields[$key] = $prop->getValue($reflectedObject);
                        }
                    }
                }
            }
        }
        print_r($fields);
        print "<br>";
        return $fields;

    }
}