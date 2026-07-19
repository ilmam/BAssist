<?php

namespace App\Support;

/**
 * Flattens nested collection/DTO data into flat, dot-prefixed rows.
 *
 * Used by the API/datatable layer to turn nested DTO structures (e.g. a row
 * with a `belongsTo` relation) into single-level rows keyed by dot paths such
 * as `category.name`. This is a serialization concern, deliberately kept out
 * of the data-access (repository) layer.
 */
class CollectionFlattener
{
    /**
     * @param  iterable|object  $collection  Anything array-castable or with toArray()
     * @param  array<int, string|array{field: string}>|null  $columns  Optional column selection/order
     * @return array<int, array<string, mixed>>
     */
    public function flatten($collection, ?array $columns = null): array
    {
        $rows = [];

        foreach ($this->toRows($collection) as $row) {
            $rows[] = $this->flattenRow((array) $row);
        }

        return $columns === null ? $rows : $this->selectColumns($rows, $columns);
    }

    /**
     * @return array<int, mixed>
     */
    protected function toRows($collection): array
    {
        if (is_object($collection) && method_exists($collection, 'toArray')) {
            return $collection->toArray();
        }

        return (array) $collection;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function flattenRow(array $row, string $prefix = ''): array
    {
        $flat = [];

        foreach ($row as $key => $value) {
            if (is_array($value)) {
                $flat = array_merge($flat, $this->flattenRow($value, "{$prefix}{$key}."));
            } else {
                $flat[$prefix.$key] = $value;
            }
        }

        return $flat;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string|array{field: string}>  $columns
     * @return array<int, array<string, mixed>>
     */
    protected function selectColumns(array $rows, array $columns): array
    {
        $selected = [];

        foreach ($rows as $row) {
            $mapped = [];

            foreach ($columns as $column) {
                $field = is_array($column) ? $column['field'] : $column;
                $mapped[$field] = $row[$field] ?? '';
            }

            $selected[] = $mapped;
        }

        return $selected;
    }
}
