<?php
namespace App\Data;

use Spatie\LaravelData\Data;
use Illuminate\Http\Request;

class BaseData extends Data
{
    const VALUE_PROPERTY_ATTRIBUTE = "App\Attributes\ValuePropertyAttribute";

    /**
     * Get array of ValueProperty fields
     * This used to retrieve columns for datatable
     */
    public function getFields($onlyHeaders = false, $withPrefix = true, $prefix = '', $object = null)
    {
        if ($object == null) {
            $object = $this;
        }

        $reflect = new \ReflectionClass($object);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

        $fields = array();
        $sep = ".";

        foreach($props as $prop) {
            $value = $prop->getValue($object);
            $fieldName = $prop->getName();
            $fullFieldName = ltrim($prefix.$sep.$fieldName, ".");

            if (is_array($value) || is_object($value)) {
                $fields = array_merge($fields, $this->getFields($onlyHeaders, $withPrefix, $fullFieldName, $value));
            } else {
                $attributes = $prop->getAttributes();
                foreach ($attributes as $attribute) {
                    if ($attribute->getName() == self::VALUE_PROPERTY_ATTRIBUTE) {
                        $key = $withPrefix ? $fullFieldName : $fieldName;
                        if ($onlyHeaders) {
                            $fields[] = $key;
                        } else {
                            $fields[$key] = $object->{$fieldName}; //$prop->getValue($object);
                        }
                    }
                }
            }
        }
        return $fields;
    }

    /**
     * Get array of ValueProperty fields
     * This used to retrieve columns for datatable
     */
    public function getColumns($onlyHeaders = false, $withPrefix = true, $prefix = '', $object = null)
    {
        if ($object == null) {
            $object = $this;
        }

        $reflect = new \ReflectionClass($object);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

        $fields = array();
        $sep = ".";

        foreach($props as $prop) {
            $value = $prop->getValue($object);
            $fieldName = $prop->getName();
            $fullFieldName = ltrim($prefix.$sep.$fieldName, ".");

            if (is_array($value) || is_object($value)) {
                $fields = array_merge($fields, $this->getFields($onlyHeaders, $withPrefix, $fullFieldName, $value));
            } else {
                $attributes = $prop->getAttributes();
                foreach ($attributes as $attribute) {
                    if ($attribute->getName() == self::VALUE_PROPERTY_ATTRIBUTE) {
                        $key = $withPrefix ? $fullFieldName : $fieldName;
                        if ($onlyHeaders) {
                            $fields[] = $key;
                        } else {
                            $fields[$key] = $prop->getValue($object);
                        }
                    }
                }
            }
        }
        return $fields;
    }

    /**
     * Return an array from data object with only ValueProperty fields
     * not used, as getFields now do both jobs
     */
    // public function flatten($object = null, $prefix = '')
    // {
    //     if ($object == null) {
    //         $object = $this;
    //     }

    //     $reflect = new \ReflectionClass($object);
    //     $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

    //     $flat = array();
    //     $sep = ".";

    //     foreach($props as $prop) {
    //         $value = $prop->getValue($object);
    //         $key = $prop->getName();
    //         $_key = ltrim($prefix.$sep.$key, ".");

    //         if (is_array($value) || is_object($value)) {
    //             //print $_key;
    //             $flat = array_merge($flat, $this->flatten($value, $_key));
    //         } else {
    //             $attributes = $prop->getAttributes();
    //             foreach ($attributes as $attribute) {
    //                 if ($attribute->getName() == self::CHECK_PROPERTY_NAME) {
    //                     $flat[$_key] = $prop->getValue($object);
    //                 }
    //             }
    //         }
    //     }
    //     return $flat;
    // }

    // public static function fromRequest($object) : static
    // {
    //     // foreach($object->input as $f) {
    //     //     print $f;
    //     // }
    //     $class_vars = get_class_vars(get_called_class());
    //     // dd($class_vars);
    //     foreach ($object as $field=>$value) {
    //         if (array_key_exists($field, $class_vars)) {
    //             $class_vars[$field] = $value;
    //         }
    //     }
    //     // dd($class_vars);
    //     return new static(...$class_vars);
    // }
}
?>