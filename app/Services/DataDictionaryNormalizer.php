<?php

namespace App\Services;

class DataDictionaryNormalizer
{
    public function __construct(
        protected DataTypeInference $inference = new DataTypeInference,
    ) {}

    /**
     * @return list<array{name: string, meaning: string, fields: list<array{name: string, meaning: string, type: string|null, is_pk: bool, references: string|null, business_may_set: bool, business_may_see: bool}>}>
     */
    public function normalize(mixed $entities): array
    {
        if (! is_array($entities)) {
            return [];
        }

        $rows = [];
        foreach ($entities as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            $meaning = trim((string) ($row['meaning'] ?? ''));
            $fields = $this->normalizeFields($row['fields'] ?? []);

            if ($name === '' && $meaning === '' && $fields === []) {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'meaning' => $meaning,
                'fields' => $fields,
            ];
        }

        $entityNames = array_values(array_filter(array_map(
            static fn (array $row): string => $row['name'],
            $rows
        )));

        foreach ($rows as &$row) {
            foreach ($row['fields'] as &$field) {
                if ($field['type'] === null || $field['type'] === '') {
                    $field['type'] = $this->inference->guess($field['name']);
                }

                if (($field['references'] === null || $field['references'] === '') && $field['name'] !== '') {
                    $guessed = $this->inference->guessReference($field['name'], $entityNames);
                    if ($guessed !== null && strcasecmp($guessed, $row['name']) !== 0) {
                        $field['references'] = $guessed;
                    }
                }
            }
            unset($field);
        }
        unset($row);

        return $rows;
    }

    /**
     * @return list<array{name: string, meaning: string, type: string|null, is_pk: bool, references: string|null, business_may_set: bool, business_may_see: bool}>
     */
    protected function normalizeFields(mixed $fields): array
    {
        if (! is_array($fields)) {
            return [];
        }

        $rows = [];
        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $name = trim((string) ($field['name'] ?? ''));
            $meaning = trim((string) ($field['meaning'] ?? ''));
            $type = strtolower(trim((string) ($field['type'] ?? '')));
            $references = trim((string) ($field['references'] ?? ''));

            if ($type === '' || $type === 'auto') {
                $type = null;
            }

            if ($type !== null && ! in_array($type, DataTypeInference::TYPES, true)) {
                $type = null;
            }

            $isPk = $this->toBool($field['is_pk'] ?? false);
            $maySet = array_key_exists('business_may_set', $field)
                ? $this->toBool($field['business_may_set'])
                : true;
            $maySee = array_key_exists('business_may_see', $field)
                ? $this->toBool($field['business_may_see'])
                : true;

            if ($name === '' && $meaning === '' && $type === null && $references === '' && ! $isPk) {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'meaning' => $meaning,
                'type' => $type,
                'is_pk' => $isPk,
                'references' => $references !== '' ? $references : null,
                'business_may_set' => $maySet,
                'business_may_see' => $maySee,
            ];
        }

        return $rows;
    }

    protected function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}
