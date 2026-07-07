<?php
namespace App\Data;

use App\Support\DtoMetadata;
use Spatie\LaravelData\Data;

class BaseData extends Data
{
    const VALUE_PROPERTY_ATTRIBUTE = "App\Attributes\ValuePropertyAttribute";

    /**
     * Get array of ValueProperty fields using cached DTO metadata.
     * Used for datatable columns and detail views.
     */
    public function getFields($onlyHeaders = false, $withPrefix = true, $prefix = '', $object = null)
    {
        if ($object == null) {
            $object = $this;
        }

        return DtoMetadata::for($object)->extractValues($object, $onlyHeaders, $withPrefix);
    }

    /**
     * Alias for getFields().
     */
    public function getColumns($onlyHeaders = false, $withPrefix = true, $prefix = '', $object = null)
    {
        return $this->getFields($onlyHeaders, $withPrefix, $prefix, $object);
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