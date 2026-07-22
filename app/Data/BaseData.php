<?php
namespace App\Data;

use App\Support\DtoMetadata;
use Spatie\LaravelData\Data;

class BaseData extends Data
{
    /**
     * Get array of Value-marked fields using cached DTO metadata.
     * Used for detail views (list columns use InList via listColumns()).
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
}
