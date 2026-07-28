<?php

namespace App\Services;

/**
 * Normalizes Architecture element/relationship JSON payloads.
 */
class C4ArchitectureNormalizer
{
    public const KINDS = ['person', 'system', 'container', 'component', 'group'];

    public const FORMS = ['box', 'database', 'queue'];

    public const REL_DIRECTIONS = ['rel', 'up', 'down', 'left', 'right', 'back', 'bi'];

    public const DEFAULT_LAYOUT = [
        'shapes_per_row' => 4,
        'boundaries_per_row' => 2,
    ];

    /**
     * @param  array<string, mixed>  $layout
     * @return array{shapes_per_row: int, boundaries_per_row: int}
     */
    public function normalizeLayout(array $layout): array
    {
        $shapes = (int) ($layout['shapes_per_row'] ?? self::DEFAULT_LAYOUT['shapes_per_row']);
        $boundaries = (int) ($layout['boundaries_per_row'] ?? self::DEFAULT_LAYOUT['boundaries_per_row']);

        return [
            'shapes_per_row' => max(1, min(12, $shapes > 0 ? $shapes : self::DEFAULT_LAYOUT['shapes_per_row'])),
            'boundaries_per_row' => max(1, min(12, $boundaries > 0 ? $boundaries : self::DEFAULT_LAYOUT['boundaries_per_row'])),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     * @return list<array{
     *   key: string,
     *   kind: string,
     *   name: string,
     *   description: string|null,
     *   technology: string|null,
     *   parent_key: string|null,
     *   external: bool,
     *   form: string,
     *   feature_ids: list<int>,
     *   style: array{bg_color: string|null, font_color: string|null, border_color: string|null}
     * }>
     */
    public function normalizeElements(array $elements): array
    {
        $rows = [];
        $seenKeys = [];

        foreach ($elements as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            $kind = strtolower(trim((string) ($row['kind'] ?? '')));
            $key = trim((string) ($row['key'] ?? ''));

            if ($name === '' && $kind === '' && $key === '') {
                continue;
            }

            if ($name === '' || ! in_array($kind, self::KINDS, true)) {
                continue;
            }

            if ($key === '') {
                $key = $this->slugKey($name, $kind);
            }

            $key = $this->sanitizeKey($key);
            if ($key === '' || isset($seenKeys[$key])) {
                $key = $this->uniqueKey($this->slugKey($name, $kind), $seenKeys);
            }
            $seenKeys[$key] = true;

            $parentKey = trim((string) ($row['parent_key'] ?? ''));
            $parentKey = $parentKey !== '' ? $this->sanitizeKey($parentKey) : null;

            $featureIds = $this->normalizeFeatureIds($row['feature_ids'] ?? []);

            $style = is_array($row['style'] ?? null) ? $row['style'] : [];

            $form = strtolower(trim((string) ($row['form'] ?? 'box')));
            if (! in_array($kind, ['system', 'container', 'component'], true)) {
                $form = 'box';
            } elseif (! in_array($form, self::FORMS, true)) {
                $form = 'box';
            }

            $rows[] = [
                'key' => $key,
                'kind' => $kind,
                'name' => $name,
                'description' => $this->nullableTrim($row['description'] ?? null),
                'technology' => $this->nullableTrim($row['technology'] ?? null),
                'parent_key' => $parentKey,
                'external' => filter_var($row['external'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'form' => $form,
                'feature_ids' => $featureIds,
                'style' => [
                    'bg_color' => $this->nullableColor($style['bg_color'] ?? $row['bg_color'] ?? null),
                    'font_color' => $this->nullableColor($style['font_color'] ?? $row['font_color'] ?? null),
                    'border_color' => $this->nullableColor($style['border_color'] ?? $row['border_color'] ?? null),
                ],
            ];
        }

        return $this->enforceParentRules($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $relationships
     * @return list<array{
     *   from_key: string,
     *   to_key: string,
     *   label: string|null,
     *   technology: string|null,
     *   direction: string,
     *   style: array{line_color: string|null}
     * }>
     */
    public function normalizeRelationships(array $relationships): array
    {
        $rows = [];

        foreach ($relationships as $row) {
            if (! is_array($row)) {
                continue;
            }

            $from = $this->sanitizeKey(trim((string) ($row['from_key'] ?? $row['from'] ?? '')));
            $to = $this->sanitizeKey(trim((string) ($row['to_key'] ?? $row['to'] ?? '')));

            if ($from === '' || $to === '') {
                continue;
            }

            $style = is_array($row['style'] ?? null) ? $row['style'] : [];
            $direction = strtolower(trim((string) ($row['direction'] ?? 'rel')));
            if ($direction === '' || ! in_array($direction, self::REL_DIRECTIONS, true)) {
                $direction = 'rel';
            }

            $rows[] = [
                'from_key' => $from,
                'to_key' => $to,
                'label' => $this->nullableTrim($row['label'] ?? null),
                'technology' => $this->nullableTrim($row['technology'] ?? null),
                'direction' => $direction,
                'style' => [
                    'line_color' => $this->nullableColor($style['line_color'] ?? $row['line_color'] ?? null),
                ],
            ];
        }

        return $rows;
    }

    /**
     * Flatten elements into parent-before-children tree order for the editor.
     *
     * @param  list<array<string, mixed>>  $elements
     * @return list<array<string, mixed>>
     */
    public function orderAsTree(array $elements): array
    {
        $byParent = [];
        foreach ($elements as $el) {
            $parent = $el['parent_key'] ?? '';
            $byParent[$parent][] = $el;
        }

        $ordered = [];
        $walk = function (string $parentKey) use (&$walk, &$ordered, $byParent): void {
            foreach ($byParent[$parentKey] ?? [] as $el) {
                $ordered[] = $el;
                $walk($el['key']);
            }
        };
        $walk('');

        // Orphans (broken parent refs) append at end.
        $seen = array_column($ordered, 'key');
        foreach ($elements as $el) {
            if (! in_array($el['key'], $seen, true)) {
                $ordered[] = $el;
            }
        }

        return $ordered;
    }

    /**
     * Nesting depth for UI indentation (0 = root).
     *
     * @param  list<array<string, mixed>>  $elements
     */
    public function depthFor(array $elements, string $key): int
    {
        $byKey = [];
        foreach ($elements as $el) {
            $byKey[$el['key']] = $el;
        }

        $depth = 0;
        $current = $byKey[$key] ?? null;
        $guard = 0;
        while ($current && ! empty($current['parent_key']) && isset($byKey[$current['parent_key']]) && $guard < 20) {
            $depth++;
            $current = $byKey[$current['parent_key']];
            $guard++;
        }

        return $depth;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function enforceParentRules(array $rows): array
    {
        $byKey = [];
        foreach ($rows as $row) {
            $byKey[$row['key']] = $row;
        }

        foreach ($rows as &$row) {
            $parent = $row['parent_key'];
            if ($parent === null || ! isset($byKey[$parent])) {
                $row['parent_key'] = null;

                continue;
            }

            $parentKind = $byKey[$parent]['kind'];

            if ($row['kind'] === 'group') {
                // Groups are flat in v1 (no nested groups).
                $row['parent_key'] = null;

                continue;
            }

            if ($row['kind'] === 'container' && $parentKind !== 'system') {
                $row['parent_key'] = null;
            }
            if ($row['kind'] === 'component' && $parentKind !== 'container') {
                $row['parent_key'] = null;
            }
            // Systems and persons may parent to a group (Move to group).
            if (in_array($row['kind'], ['person', 'system'], true) && $parentKind !== 'group') {
                $row['parent_key'] = null;
            }
        }
        unset($row);

        return array_values($rows);
    }

    public function sanitizeKey(string $key): string
    {
        $key = preg_replace('/[^A-Za-z0-9_]/', '', $key) ?? '';
        if ($key === '') {
            return '';
        }
        if (preg_match('/^\d/', $key)) {
            $key = 'E'.$key;
        }

        return $key;
    }

    public function slugKey(string $name, string $kind = 'E'): string
    {
        $parts = preg_split('/[^A-Za-z0-9]+/', $name) ?: [];
        $parts = array_values(array_filter($parts));
        if ($parts === []) {
            return $this->sanitizeKey(ucfirst($kind));
        }

        $id = implode('', array_map(
            static fn (string $part): string => ucfirst(strtolower($part)),
            $parts
        ));

        return $this->sanitizeKey($id);
    }

    /**
     * @param  array<string, bool>  $seen
     */
    protected function uniqueKey(string $base, array &$seen): string
    {
        $base = $base !== '' ? $base : 'Element';
        $candidate = $base;
        $i = 2;
        while (isset($seen[$candidate])) {
            $candidate = $base.$i;
            $i++;
        }
        $seen[$candidate] = true;

        return $candidate;
    }

    protected function nullableTrim(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : null;
    }

    protected function nullableColor(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }
        if (! preg_match('/^#?[A-Fa-f0-9]{3,8}$|^[A-Za-z]+$/', $text)) {
            return null;
        }

        return $text;
    }

    /**
     * @return list<int>
     */
    protected function normalizeFeatureIds(mixed $raw): array
    {
        if (! is_array($raw)) {
            if (is_string($raw) && $raw !== '') {
                $raw = preg_split('/\s*,\s*/', $raw) ?: [];
            } else {
                return [];
            }
        }

        $ids = [];
        foreach ($raw as $id) {
            if (is_numeric($id) && (int) $id > 0) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
