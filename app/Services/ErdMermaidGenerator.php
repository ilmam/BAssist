<?php

namespace App\Services;

class ErdMermaidGenerator
{
    public function __construct(
        protected DataTypeInference $inference = new DataTypeInference,
        protected DataDictionaryNormalizer $normalizer = new DataDictionaryNormalizer,
    ) {}

    /**
     * @param  'conceptual'|'design'  $level
     */
    public function generate(mixed $entities, string $level = 'design'): string
    {
        $withFields = $level !== 'conceptual';
        $rows = $this->normalizer->normalize($entities);
        $named = array_values(array_filter($rows, static fn (array $row): bool => $row['name'] !== ''));

        if ($named === []) {
            return "erDiagram\n";
        }

        $lines = ['erDiagram'];
        $idsByName = [];
        foreach ($named as $row) {
            $idsByName[strtolower($row['name'])] = $this->inference->toEntityId($row['name']);
        }

        $relationKeys = [];
        foreach ($named as $row) {
            $fromId = $idsByName[strtolower($row['name'])];
            foreach ($row['fields'] as $field) {
                $target = $field['references'] ?? null;
                if ($target === null || $target === '') {
                    continue;
                }

                $toKey = strtolower($target);
                if (! isset($idsByName[$toKey]) || $toKey === strtolower($row['name'])) {
                    continue;
                }

                $pair = $idsByName[$toKey].'|'.$fromId;
                if (isset($relationKeys[$pair])) {
                    continue;
                }
                $relationKeys[$pair] = true;

                $label = $this->relationLabel($field['name']);
                $lines[] = '    '.$idsByName[$toKey].' ||--o{ '.$fromId.' : '.$label;
            }
        }

        foreach ($named as $row) {
            $id = $idsByName[strtolower($row['name'])];
            if (! $withFields) {
                $lines[] = '    '.$id;

                continue;
            }

            $lines[] = '    '.$id.' {';

            foreach ($row['fields'] as $field) {
                if (($field['name'] ?? '') === '') {
                    continue;
                }

                $type = $this->mermaidType($field['type'] ?? null);
                $markers = [];
                if (! empty($field['is_pk'])) {
                    $markers[] = 'PK';
                }
                if (! empty($field['references'])) {
                    $markers[] = 'FK';
                }

                $line = '        '.$type.' '.$this->fieldId($field['name']);
                if ($markers !== []) {
                    $line .= ' '.implode(',', $markers);
                }
                $lines[] = $line;
            }

            $lines[] = '    }';
        }

        return implode("\n", $lines)."\n";
    }

    protected function mermaidType(?string $type): string
    {
        return match ($type) {
            'int' => 'int',
            'datetime' => 'datetime',
            'decimal' => 'decimal',
            'bool' => 'bool',
            'text' => 'text',
            default => 'string',
        };
    }

    protected function fieldId(string $name): string
    {
        $id = preg_replace('/[^A-Za-z0-9_]/', '_', trim($name)) ?? 'field';

        return $id !== '' ? $id : 'field';
    }

    protected function relationLabel(string $fieldName): string
    {
        $label = preg_replace('/_id$/i', '', trim($fieldName)) ?? '';
        $label = str_replace('_', ' ', $label);
        $label = trim($label);

        if ($label === '') {
            $label = 'has';
        }

        return '"'.$this->escape($label).'"';
    }

    protected function escape(string $value): string
    {
        return str_replace(['"', "\n", "\r"], ['\"', ' ', ' '], $value);
    }
}
