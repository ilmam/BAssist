<?php

namespace App\Services;

/**
 * Name → type defaults for data-dictionary fields. Always overridable.
 *
 * Does not invent length, enums, or defaults.
 */
class DataTypeInference
{
    public const TYPES = ['int', 'string', 'text', 'datetime', 'decimal', 'bool'];

    public function guess(string $name): ?string
    {
        $normalized = strtolower(trim($name));
        if ($normalized === '') {
            return null;
        }

        if (str_ends_with($normalized, '_id') || $normalized === 'id') {
            return 'int';
        }

        if (
            str_ends_with($normalized, '_date')
            || str_ends_with($normalized, '_at')
            || $normalized === 'date'
        ) {
            return 'datetime';
        }

        if (in_array($normalized, ['name', 'description', 'title', 'code'], true)) {
            return 'string';
        }

        if (in_array($normalized, ['details', 'notes', 'body', 'comment', 'comments'], true)) {
            return 'text';
        }

        if (
            str_contains($normalized, 'amount')
            || str_contains($normalized, 'price')
            || str_contains($normalized, 'qty')
            || $normalized === 'quantity'
        ) {
            return str_contains($normalized, 'qty') || $normalized === 'quantity' ? 'int' : 'decimal';
        }

        if (
            str_starts_with($normalized, 'is_')
            || str_starts_with($normalized, 'has_')
            || str_ends_with($normalized, '_flag')
        ) {
            return 'bool';
        }

        return null;
    }

    public function guessReference(string $fieldName, array $entityNames): ?string
    {
        $normalized = strtolower(trim($fieldName));
        if (! str_ends_with($normalized, '_id')) {
            return null;
        }

        $stem = substr($normalized, 0, -3);
        if ($stem === '') {
            return null;
        }

        $stemId = strtolower($this->toEntityId($stem));

        foreach ($entityNames as $name) {
            $entity = trim((string) $name);
            if ($entity === '') {
                continue;
            }

            $entityId = strtolower($this->toEntityId($entity));
            if ($entityId === $stemId || str_ends_with($entityId, $stemId)) {
                return $entity;
            }

            if (strtolower($entity) === $stem) {
                return $entity;
            }
        }

        return null;
    }

    public function toEntityId(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed !== '' && preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $trimmed) === 1) {
            return $trimmed;
        }

        $parts = preg_split('/[^A-Za-z0-9]+/', $trimmed) ?: [];
        $parts = array_values(array_filter($parts, fn ($part) => $part !== ''));

        if ($parts === []) {
            return 'Entity';
        }

        $id = implode('', array_map(
            static fn (string $part): string => ucfirst(strtolower($part)),
            $parts
        ));

        if ($id === '' || preg_match('/^\d/', $id) === 1) {
            $id = 'E'.$id;
        }

        return $id;
    }
}
