<?php
namespace App\Helpers;

class ArrayHelper
{
    /**
     * This is the equivalant for BaseData.getFields but for array instead of dto
     */
    public static function squash_array($onlyHeaders = true, $withPrefix = true, $prefix = '', $array = null)
    {
        $fields = array();
        $sep = ".";

        foreach($array as $fieldName => $value) {
            $fullFieldName = ltrim($prefix.$sep.$fieldName, ".");

            if (is_array($value) || is_object($value)) {
                $fields = array_merge($fields, self::squash_array($onlyHeaders, $withPrefix, $fullFieldName, $value));
            } else {
                $key = $withPrefix ? $fullFieldName : $fieldName;
                if ($onlyHeaders) {
                    $fields[] = $key;
                } else {
                    $fields[$key] = $value;
                }
            }
        }
        return $fields;
    }
}