<?php

namespace App\Services;

/**
 * Stub pack from a dictionary. Only names, types, keys, and stated references.
 */
class DataDictionaryStubExporter
{
    public function __construct(
        protected DataDictionaryNormalizer $normalizer = new DataDictionaryNormalizer,
        protected DataTypeInference $inference = new DataTypeInference,
    ) {}

    /**
     * @param  list<array<string, mixed>>|mixed  $entities
     */
    public function toCsharp(mixed $entities): string
    {
        $rows = $this->namedEntities($entities);
        $chunks = [
            '// Stub only. Generated from a BAssist data dictionary.',
            '// Developers own behaviour, validation length, defaults, and persistence details.',
            'using System;',
            'using System.Collections.Generic;',
            '',
        ];

        foreach ($rows as $entity) {
            $class = $this->inference->toEntityId($entity['name']);
            $chunks[] = 'public class '.$class;
            $chunks[] = '{';

            foreach ($entity['fields'] as $field) {
                if ($field['name'] === '') {
                    continue;
                }

                $prop = $this->pascal($field['name']);
                $type = $this->csharpType($field['type'] ?? null);
                $chunks[] = '    public '.$type.' '.$prop.' { get; set; }';
            }

            foreach ($entity['fields'] as $field) {
                $target = trim((string) ($field['references'] ?? ''));
                if ($target === '') {
                    continue;
                }
                $nav = $this->inference->toEntityId($target);
                $chunks[] = '    public '.$nav.'? '.$nav.' { get; set; }';
            }

            foreach ($this->childrenOf($entity['name'], $rows) as $child) {
                $childClass = $this->inference->toEntityId($child['name']);
                $chunks[] = '    public ICollection<'.$childClass.'> '.$childClass.'s { get; set; } = new List<'.$childClass.'>();';
            }

            $chunks[] = '}';
            $chunks[] = '';
        }

        return rtrim(implode("\n", $chunks))."\n";
    }

    /**
     * @param  list<array<string, mixed>>|mixed  $entities
     */
    public function toPhp(mixed $entities): string
    {
        $rows = $this->namedEntities($entities);
        $chunks = [
            '<?php',
            '',
            '// Stub only. Generated from a BAssist data dictionary.',
            '// Developers own behaviour, casts beyond stated types, and table names.',
            '',
        ];

        foreach ($rows as $entity) {
            $class = $this->inference->toEntityId($entity['name']);
            $fillable = [];
            $casts = [];

            foreach ($entity['fields'] as $field) {
                if ($field['name'] === '' || $field['is_pk']) {
                    continue;
                }
                $fillable[] = $field['name'];
                $cast = $this->phpCast($field['type'] ?? null);
                if ($cast !== null) {
                    $casts[] = "        '".$field['name']."' => '".$cast."',";
                }
            }

            $chunks[] = 'class '.$class;
            $chunks[] = '{';
            $chunks[] = '    protected $fillable = [';
            foreach ($fillable as $name) {
                $chunks[] = "        '".$name."',";
            }
            $chunks[] = '    ];';

            if ($casts !== []) {
                $chunks[] = '';
                $chunks[] = '    protected $casts = [';
                foreach ($casts as $line) {
                    $chunks[] = $line;
                }
                $chunks[] = '    ];';
            }

            foreach ($entity['fields'] as $field) {
                $target = trim((string) ($field['references'] ?? ''));
                if ($target === '') {
                    continue;
                }
                $related = $this->inference->toEntityId($target);
                $method = lcfirst($related);
                $chunks[] = '';
                $chunks[] = '    public function '.$method.'()';
                $chunks[] = '    {';
                $chunks[] = '        return $this->belongsTo('.$related.'::class);';
                $chunks[] = '    }';
            }

            foreach ($this->childrenOf($entity['name'], $rows) as $child) {
                $childClass = $this->inference->toEntityId($child['name']);
                $method = lcfirst($childClass).'s';
                $chunks[] = '';
                $chunks[] = '    public function '.$method.'()';
                $chunks[] = '    {';
                $chunks[] = '        return $this->hasMany('.$childClass.'::class);';
                $chunks[] = '    }';
            }

            $chunks[] = '}';
            $chunks[] = '';
        }

        return rtrim(implode("\n", $chunks))."\n";
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function namedEntities(mixed $entities): array
    {
        return array_values(array_filter(
            $this->normalizer->normalize($entities),
            static fn (array $row): bool => $row['name'] !== ''
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function childrenOf(string $parentName, array $rows): array
    {
        $children = [];

        foreach ($rows as $row) {
            if ($row['name'] === '' || strcasecmp($row['name'], $parentName) === 0) {
                continue;
            }

            foreach ($row['fields'] as $field) {
                if (strcasecmp((string) ($field['references'] ?? ''), $parentName) === 0) {
                    $children[] = $row;
                    break;
                }
            }
        }

        return $children;
    }

    protected function csharpType(?string $type): string
    {
        return match ($type) {
            'int' => 'int',
            'decimal' => 'decimal',
            'datetime' => 'DateTime',
            'bool' => 'bool',
            default => 'string',
        };
    }

    protected function phpCast(?string $type): ?string
    {
        return match ($type) {
            'int' => 'integer',
            'decimal' => 'decimal',
            'datetime' => 'datetime',
            'bool' => 'boolean',
            default => null,
        };
    }

    protected function pascal(string $name): string
    {
        return $this->inference->toEntityId($name);
    }
}
