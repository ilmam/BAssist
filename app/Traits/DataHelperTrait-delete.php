<?php

namespace App\Traits;

use App\Attributes\ValuePropertyAttribute;

/**
 * @deprecated Legacy helper kept for reference only. Not autoloaded (filename
 * intentionally does not match the class name) and slated for deletion.
 * Collection flattening now lives in App\Support\CollectionFlattener.
 */
trait DataHelperTrait
{
    public function flatten_object($object, $columns = null)
    {
        $object = get_object_vars($object);
        $flat = $this->squash($object);

        if ($columns === null) {
            return $flat;
        }

        return $this->filter_columns($flat, $columns);
    }

    protected function squash($array, string $prefix = ''): array
    {
        $flat = [];
        $sep = '.';

        if (! is_array($array)) {
            $array = (array) $array;
        }

        foreach ($array as $key => $value) {
            $_key = ltrim($prefix.$sep.$key, '.');

            if (is_array($value) || is_object($value)) {
                $flat = array_merge($flat, $this->squash((array) $value, $_key));
            } else {
                $flat[$_key] = $value;
            }
        }

        return $flat;
    }

    public function filter_columns($array, $columns)
    {
        foreach ($array as $key => $value) {
            if (! in_array($key, $columns, true)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    public function flatten_collection($collection, $columns = null)
    {
        $array = $collection->toArray();
        $flat = [];

        foreach ($array as $row) {
            $flat[] = $this->flatten_collection_array($row, '');
        }

        if ($columns === null) {
            return $flat;
        }

        return $this->map_columns($flat, $columns);
    }

    protected function flatten_collection_array(array $arr, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $flattened = array_merge($flattened, $this->flatten_collection_array($value, "{$prefix}{$key}."));
            } else {
                $flattened[$prefix.$key] = $value;
            }
        }

        return $flattened;
    }

    protected function map_columns($array, $columns)
    {
        $mapped = [];

        foreach ($array as $row) {
            $mapped_row = [];

            foreach ($columns as $col) {
                if (is_array($col)) {
                    $mapped_row[$col['field']] = $row[$col['field']] ?? '';
                } else {
                    $mapped_row[$col] = $row[$col] ?? '';
                }
            }

            $mapped[] = $mapped_row;
        }

        return $mapped;
    }

    public function getListArray($collection, $extractFields)
    {
        $list = [];
        $idField = 'id';
        $labelField = '';

        if (! is_array($extractFields)) {
            $labelField = $extractFields;
        } else {
            if (isset($extractFields['id'])) {
                $idField = $extractFields['id'];
            }
            if (isset($extractFields['label'])) {
                $labelField = $extractFields['label'];
            }
        }

        foreach ($collection as $item) {
            $list[$item[$idField]] = $item[$labelField];
        }

        return $list;
    }
}
