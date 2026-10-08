<?php

namespace App\Services;

use App\Models\Screen;
use App\Models\ScreenElement;

/**
 * Composes a screen's element rows into PlantUML Salt (docs/design-layer.md).
 * Rows are the source of truth; the Salt text is never stored.
 *
 * Rows are a tree: a row may sit inside a container row (panel, columns, column,
 * table) through its parent key. Order comes from `position`. Leaf elements that
 * share a non-null `row` hint within the same container are put on one line,
 * left to right, at the place of the first of them.
 *
 *   panel    a framed box titled by its label
 *   columns  its children side by side, one column each
 *   column   an unframed group (a column of stacked elements)
 *   table    a grid; its tablerow children are lines whose cells are split by "|".
 *            The first line is the header.
 */
class SaltScreenAssembler
{
    public const LEAF_KINDS = ['label', 'input', 'button', 'checkbox', 'radio', 'select', 'separator', 'tablerow'];

    public const CONTAINER_KINDS = ['panel', 'columns', 'column', 'table'];

    public const KINDS = [
        'label', 'input', 'button', 'checkbox', 'radio', 'select', 'separator',
        'panel', 'columns', 'column', 'table', 'tablerow',
    ];

    public function assembleScreen(Screen $screen): string
    {
        $screen->loadMissing('screenElements');

        return $this->assemble(
            (string) $screen->title,
            $screen->screenElements
                ->map(fn (ScreenElement $e) => [
                    'key' => (string) $e->id,
                    'parent_key' => $e->parent_id !== null ? (string) $e->parent_id : null,
                    'position' => (int) $e->position,
                    'row' => $e->row !== null ? (int) $e->row : null,
                    'kind' => (string) $e->kind,
                    'label' => (string) $e->label,
                ])
                ->all(),
        );
    }

    /**
     * Editor rows to assembler rows: order is the row order, blank rows are dropped
     * (separators and containers need no label), an unknown kind is a label, and a
     * parent must be a container that comes earlier (anything else sits at the top).
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{id: int|null, key: string, parent_key: string|null, position: int, row: int|null, kind: string, label: string, functional_requirement_id: int|null}>
     */
    public function normalizeRows(array $rows): array
    {
        $result = [];
        $containers = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $kind = trim((string) ($row['kind'] ?? ''));
            $kind = in_array($kind, self::KINDS, true) ? $kind : 'label';
            $label = trim((string) ($row['label'] ?? ''));

            if ($label === '' && $kind !== 'separator' && ! in_array($kind, self::CONTAINER_KINDS, true)) {
                continue;
            }

            $key = trim((string) ($row['key'] ?? ''));
            $key = $key !== '' ? $key : 'row-'.$index;
            $parent = trim((string) ($row['parent_key'] ?? ''));
            $parent = $parent !== '' && isset($containers[$parent]) && $parent !== $key ? $parent : null;

            $hint = $row['row'] ?? null;
            $requirement = $row['functional_requirement_id'] ?? null;

            $result[] = [
                'id' => isset($row['id']) && is_numeric($row['id']) ? (int) $row['id'] : null,
                'key' => $key,
                'parent_key' => $parent,
                'position' => count($result),
                'row' => $hint !== null && $hint !== '' && is_numeric($hint) ? (int) $hint : null,
                'kind' => $kind,
                'label' => $label,
                'functional_requirement_id' => $requirement !== null && $requirement !== '' && is_numeric($requirement) ? (int) $requirement : null,
            ];

            if (in_array($kind, self::CONTAINER_KINDS, true)) {
                $containers[$key] = true;
            }
        }

        return $result;
    }

    /**
     * @param  list<array{key?: string, parent_key?: string|null, position?: int, row?: int|null, kind: string, label: string}>  $elements
     */
    public function assemble(string $title, array $elements): string
    {
        usort($elements, fn (array $a, array $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

        $children = [];
        foreach ($elements as $element) {
            $children[(string) ($element['parent_key'] ?? '')][] = $element;
        }

        $body = $this->level($children, '');

        return implode("\n", [
            '@startsalt',
            '{+',
            '  <b>'.$this->clean($title),
            '  --',
            ...$this->indent($body, 1),
            '}',
            '@endsalt',
        ]);
    }

    /**
     * Lines for the children of one container (or the top level when $parent is '').
     *
     * @param  array<string, list<array<string, mixed>>>  $children
     * @return list<string>
     */
    protected function level(array $children, string $parent): array
    {
        $items = [];
        $hinted = [];

        foreach ($children[$parent] ?? [] as $element) {
            $kind = (string) ($element['kind'] ?? 'label');
            $key = (string) ($element['key'] ?? '');

            if (in_array($kind, self::CONTAINER_KINDS, true)) {
                $items[] = ['block' => $this->container($children, $element)];

                continue;
            }

            $cell = $this->widget($kind, (string) ($element['label'] ?? ''));
            $row = $element['row'] ?? null;

            if ($row === null) {
                $items[] = ['cells' => [$cell]];
            } elseif (isset($hinted[$row])) {
                $items[$hinted[$row]]['cells'][] = $cell;
            } else {
                $hinted[$row] = count($items);
                $items[] = ['cells' => [$cell]];
            }
        }

        $lines = [];
        foreach ($items as $item) {
            if (isset($item['block'])) {
                array_push($lines, ...$item['block']);
            } else {
                $lines[] = implode(' | ', $item['cells']);
            }
        }

        return $lines;
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $children
     * @param  array<string, mixed>  $element
     * @return list<string>
     */
    protected function container(array $children, array $element): array
    {
        $kind = (string) $element['kind'];
        $key = (string) ($element['key'] ?? '');
        $title = $this->clean((string) ($element['label'] ?? ''));

        if ($kind === 'table') {
            return $this->table($children[$key] ?? []);
        }

        if ($kind === 'columns') {
            return $this->columns($children, $key);
        }

        $inner = $this->level($children, $key);
        $head = $title !== '' ? ['<b>'.$title, '--'] : [];

        return [$kind === 'panel' ? '{+' : '{', ...$this->indent([...$head, ...$inner], 1), '}'];
    }

    /**
     * Each child is one column; a leaf child becomes a column of its own.
     *
     * @param  array<string, list<array<string, mixed>>>  $children
     * @return list<string>
     */
    protected function columns(array $children, string $key): array
    {
        $blocks = [];

        foreach ($children[$key] ?? [] as $child) {
            $kind = (string) ($child['kind'] ?? 'label');

            $blocks[] = in_array($kind, self::CONTAINER_KINDS, true)
                ? $this->container($children, $child)
                : ['{', '  '.$this->widget($kind, (string) ($child['label'] ?? '')), '}'];
        }

        if ($blocks === []) {
            return ['{', '}'];
        }

        // Join "}" and the next "{" into one "} | {" line, the way Salt writes side-by-side groups.
        $merged = array_shift($blocks);
        foreach ($blocks as $block) {
            $close = array_pop($merged);
            $open = array_shift($block);
            $merged[] = $close.' | '.$open;
            array_push($merged, ...$block);
        }

        return ['{', ...$this->indent($merged, 1), '}'];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<string>
     */
    protected function table(array $rows): array
    {
        $lines = [];

        foreach ($rows as $i => $row) {
            $cells = array_map(fn (string $cell) => $this->clean($cell), explode('|', (string) ($row['label'] ?? '')));
            $cells = array_map(fn (string $cell) => $i === 0 && $cell !== '' ? '<b>'.$cell : $cell, $cells);
            $lines[] = implode(' | ', $cells);
        }

        return ['{#', ...$this->indent($lines, 1), '}'];
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    protected function indent(array $lines, int $levels): array
    {
        $pad = str_repeat('  ', $levels);

        return array_map(fn (string $line) => $pad.$line, $lines);
    }

    protected function widget(string $kind, string $label): string
    {
        $text = $this->clean($label);

        return match ($kind) {
            'input' => '"'.str_pad($text, 14).'"',
            'button' => '['.$text.']',
            'checkbox' => '[ ] '.$text,
            'radio' => '() '.$text,
            'select' => '^'.$text.'^',
            'separator' => '--',
            'tablerow' => implode(' | ', array_map(fn (string $cell) => $this->clean($cell), explode('|', $label))),
            default => $text,
        };
    }

    /** Salt's own punctuation would change the layout; labels are plain text. */
    protected function clean(string $text): string
    {
        $text = preg_replace('/[|"\[\]^{}]+/', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }
}
