<?php

namespace App\Services;

/**
 * One-shot C# EF stubs from a data dictionary.
 * Emits only names, types, keys, and navigations the BA captured. No MaxLength, defaults, or table poetry.
 */
class EfModelStubExporter
{
    public function __construct(
        protected DataTypeInference $inference = new DataTypeInference,
        protected DataDictionaryNormalizer $normalizer = new DataDictionaryNormalizer,
    ) {}

    public function export(string $title, mixed $entities): string
    {
        $rows = $this->normalizer->normalize($entities);
        $named = array_values(array_filter($rows, static fn (array $row): bool => $row['name'] !== ''));
        $namespace = $this->namespaceFromTitle($title);

        $blocks = [];
        $blocks[] = '// Stub generated from the BAssist data dictionary. Developers own everything after this.';
        $blocks[] = 'using System;';
        $blocks[] = 'using System.Collections.Generic;';
        $blocks[] = 'using System.ComponentModel.DataAnnotations;';
        $blocks[] = 'using System.ComponentModel.DataAnnotations.Schema;';
        $blocks[] = '';
        $blocks[] = 'namespace '.$namespace;
        $blocks[] = '{';

        if ($named === []) {
            $blocks[] = '    // No entities captured yet.';
            $blocks[] = '}';

            return implode("\n", $blocks)."\n";
        }

        $idsByName = [];
        foreach ($named as $row) {
            $idsByName[strtolower($row['name'])] = $this->inference->toEntityId($row['name']);
        }

        $classChunks = [];
        foreach ($named as $row) {
            $classChunks[] = $this->classBlock($row, $idsByName, $named);
        }

        $blocks[] = implode("\n\n", $classChunks);
        $blocks[] = '}';

        return implode("\n", $blocks)."\n";
    }

    /**
     * @param  array{name: string, meaning: string, fields: list<array<string, mixed>>}  $row
     * @param  array<string, string>  $idsByName
     * @param  list<array{name: string, meaning: string, fields: list<array<string, mixed>>}>  $all
     */
    protected function classBlock(array $row, array $idsByName, array $all): string
    {
        $class = $idsByName[strtolower($row['name'])];
        $lines = [];
        if ($row['meaning'] !== '') {
            $lines[] = '    /// <summary>'.$this->xml($row['meaning']).'</summary>';
        }
        $lines[] = '    public class '.$class;
        $lines[] = '    {';

        $fields = $row['fields'];
        if ($fields === []) {
            $lines[] = '        [Key]';
            $lines[] = '        public int Id { get; set; }';
        }

        foreach ($fields as $field) {
            if (($field['name'] ?? '') === '') {
                continue;
            }

            $prop = $this->toPascal($field['name']);
            $csType = $this->csharpType($field['type'] ?? null);
            $comments = [];
            if (($field['meaning'] ?? '') !== '') {
                $comments[] = $field['meaning'];
            }
            if (empty($field['business_may_set'])) {
                $comments[] = 'Business may not set.';
            }
            if (empty($field['business_may_see'])) {
                $comments[] = 'Business may not see.';
            }
            if ($comments !== []) {
                $lines[] = '        /// <summary>'.$this->xml(implode(' ', $comments)).'</summary>';
            }
            if (! empty($field['is_pk'])) {
                $lines[] = '        [Key]';
            }

            $ref = $field['references'] ?? null;
            if (is_string($ref) && $ref !== '' && isset($idsByName[strtolower($ref)])) {
                $lines[] = '        [ForeignKey(nameof('.$this->toPascal($ref).'))]';
            }

            $lines[] = '        public '.$csType.' '.$prop.' { get; set; }'.$this->defaultAssignment($csType);
            $lines[] = '';
        }

        foreach ($fields as $field) {
            $ref = $field['references'] ?? null;
            if (! is_string($ref) || $ref === '' || ! isset($idsByName[strtolower($ref)])) {
                continue;
            }
            $nav = $idsByName[strtolower($ref)];
            $lines[] = '        public virtual '.$nav.'? '.$nav.' { get; set; }';
        }

        foreach ($all as $other) {
            if (strcasecmp($other['name'], $row['name']) === 0) {
                continue;
            }
            $pointsHere = false;
            foreach ($other['fields'] as $field) {
                $ref = $field['references'] ?? null;
                if (is_string($ref) && strcasecmp($ref, $row['name']) === 0) {
                    $pointsHere = true;
                    break;
                }
            }
            if (! $pointsHere) {
                continue;
            }
            $otherClass = $idsByName[strtolower($other['name'])];
            $lines[] = '        public virtual ICollection<'.$otherClass.'> '.$otherClass.'s { get; set; } = new List<'.$otherClass.'>();';
        }

        // Drop trailing empty line inside class if last field added one.
        while (end($lines) === '') {
            array_pop($lines);
        }

        $lines[] = '    }';

        return implode("\n", $lines);
    }

    protected function csharpType(?string $type): string
    {
        return match ($type) {
            'int' => 'int',
            'datetime' => 'DateTime',
            'decimal' => 'decimal',
            'bool' => 'bool',
            'text', 'string' => 'string',
            default => 'string',
        };
    }

    protected function defaultAssignment(string $csType): string
    {
        return $csType === 'string' ? ' = string.Empty;' : '';
    }

    protected function toPascal(string $name): string
    {
        $parts = preg_split('/[^A-Za-z0-9]+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts, fn ($part) => $part !== ''));

        if ($parts === []) {
            return 'Field';
        }

        return implode('', array_map(
            static fn (string $part): string => ucfirst(strtolower($part)),
            $parts
        ));
    }

    protected function namespaceFromTitle(string $title): string
    {
        $id = $this->inference->toEntityId($title !== '' ? $title : 'Dictionary');

        return 'BAssist.'.$id;
    }

    protected function xml(string $value): string
    {
        return htmlspecialchars(str_replace(["\n", "\r"], ' ', $value), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
