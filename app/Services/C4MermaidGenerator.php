<?php

namespace App\Services;

/**
 * Converts Architecture elements/relationships into Mermaid C4 diagrams.
 */
class C4MermaidGenerator
{
    public function __construct(
        protected C4ArchitectureNormalizer $normalizer = new C4ArchitectureNormalizer,
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     * @param  list<array<string, mixed>>  $relationships
     * @param  array{shapes_per_row?: int, boundaries_per_row?: int}  $layout
     */
    public function toContext(array $elements, array $relationships, array $layout = []): string
    {
        $elements = $this->normalizer->normalizeElements($elements);
        $relationships = $this->normalizer->normalizeRelationships($relationships);
        $layout = $this->normalizer->normalizeLayout($layout);

        $groups = array_values(array_filter(
            $elements,
            static fn (array $el): bool => $el['kind'] === 'group'
        ));
        $groupKeys = array_column($groups, 'key');

        $ungrouped = array_values(array_filter(
            $elements,
            static fn (array $el): bool => in_array($el['kind'], ['person', 'system'], true)
                && ($el['parent_key'] === null || ! in_array($el['parent_key'], $groupKeys, true))
        ));

        $lines = ['C4Context'];
        $lines[] = '  '.$this->layoutConfigLine($layout);
        $relKeys = [];

        foreach ($ungrouped as $el) {
            $lines[] = '  '.$this->elementDeclaration($el);
            $relKeys[] = $el['key'];
        }

        foreach ($groups as $group) {
            $children = array_values(array_filter(
                $elements,
                static fn (array $el): bool => in_array($el['kind'], ['person', 'system'], true)
                    && $el['parent_key'] === $group['key']
            ));
            $boundaryId = $this->normalizer->sanitizeKey($group['key']);
            $lines[] = '  Boundary('.$boundaryId.', "'.$this->escape($group['name']).'") {';
            foreach ($children as $el) {
                $lines[] = '    '.$this->elementDeclaration($el);
                $relKeys[] = $el['key'];
            }
            $lines[] = '  }';
        }

        foreach ($this->relsAmong($relationships, $relKeys) as $rel) {
            $lines[] = '  '.$this->relDeclaration($rel);
        }

        foreach ($elements as $el) {
            if (! in_array($el['kind'], ['person', 'system'], true)) {
                continue;
            }
            $style = $this->styleLine($el);
            if ($style !== null) {
                $lines[] = '  '.$style;
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     * @param  list<array<string, mixed>>  $relationships
     * @param  array{shapes_per_row?: int, boundaries_per_row?: int}  $layout
     */
    public function toContainer(array $elements, array $relationships, ?string $systemKey = null, array $layout = []): string
    {
        $elements = $this->normalizer->normalizeElements($elements);
        $relationships = $this->normalizer->normalizeRelationships($relationships);
        $layout = $this->normalizer->normalizeLayout($layout);

        $system = $this->resolveSystem($elements, $systemKey);
        if ($system === null) {
            return "C4Container\n";
        }

        $containers = array_values(array_filter(
            $elements,
            static fn (array $el): bool => $el['kind'] === 'container' && $el['parent_key'] === $system['key']
        ));

        $externals = array_values(array_filter(
            $elements,
            static fn (array $el): bool => ($el['kind'] === 'system' && $el['external'] === true && $el['key'] !== $system['key'])
                || $el['kind'] === 'person'
        ));

        // Only keys that are actually declared as nodes (not the boundary id).
        $declaredKeys = array_merge(
            array_column($containers, 'key'),
            array_column($externals, 'key'),
        );

        $lines = ['C4Container'];
        $lines[] = '  '.$this->layoutConfigLine($layout);

        foreach ($externals as $el) {
            $lines[] = '  '.$this->elementDeclaration($el);
        }

        // Official Mermaid C4Container examples wrap containers in System_Boundary.
        $boundaryId = $this->normalizer->sanitizeKey($system['key'].'Boundary');
        $lines[] = '  System_Boundary('.$boundaryId.', "'.$this->escape($system['name']).'") {';
        foreach ($containers as $el) {
            $lines[] = '    '.$this->elementDeclaration($el);
        }
        $lines[] = '  }';

        foreach ($this->relsAmong($relationships, $declaredKeys) as $rel) {
            $lines[] = '  '.$this->relDeclaration($rel);
        }

        foreach (array_merge($containers, $externals) as $el) {
            $style = $this->styleLine($el);
            if ($style !== null) {
                $lines[] = '  '.$style;
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     * @param  list<array<string, mixed>>  $relationships
     * @param  array{shapes_per_row?: int, boundaries_per_row?: int}  $layout
     */
    public function toComponent(array $elements, array $relationships, ?string $containerKey = null, array $layout = []): string
    {
        $elements = $this->normalizer->normalizeElements($elements);
        $relationships = $this->normalizer->normalizeRelationships($relationships);
        $layout = $this->normalizer->normalizeLayout($layout);

        $container = $this->resolveContainer($elements, $containerKey);
        if ($container === null) {
            return "C4Component\n";
        }

        $components = array_values(array_filter(
            $elements,
            static fn (array $el): bool => $el['kind'] === 'component' && $el['parent_key'] === $container['key']
        ));

        $siblings = array_values(array_filter(
            $elements,
            static fn (array $el): bool => $el['kind'] === 'container'
                && $el['parent_key'] === $container['parent_key']
                && $el['key'] !== $container['key']
        ));

        $people = array_values(array_filter(
            $elements,
            static fn (array $el): bool => $el['kind'] === 'person'
        ));

        $declaredKeys = array_merge(
            array_column($components, 'key'),
            array_column($siblings, 'key'),
            array_column($people, 'key'),
        );

        $lines = ['C4Component'];
        $lines[] = '  '.$this->layoutConfigLine($layout);

        foreach ($people as $el) {
            $lines[] = '  '.$this->elementDeclaration($el);
        }
        foreach ($siblings as $el) {
            $lines[] = '  '.$this->elementDeclaration($el);
        }

        $boundaryId = $this->normalizer->sanitizeKey($container['key'].'Boundary');
        $lines[] = '  Container_Boundary('.$boundaryId.', "'.$this->escape($container['name']).'") {';
        foreach ($components as $el) {
            $lines[] = '    '.$this->elementDeclaration($el);
        }
        $lines[] = '  }';

        foreach ($this->relsAmong($relationships, $declaredKeys) as $rel) {
            $lines[] = '  '.$this->relDeclaration($rel);
        }

        foreach (array_merge($components, $siblings, $people) as $el) {
            $style = $this->styleLine($el);
            if ($style !== null) {
                $lines[] = '  '.$style;
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     * @return array<string, mixed>|null
     */
    public function resolveSystem(array $elements, ?string $systemKey): ?array
    {
        $systems = array_values(array_filter(
            $elements,
            static fn (array $el): bool => $el['kind'] === 'system' && $el['external'] !== true
        ));

        if ($systems === []) {
            $systems = array_values(array_filter(
                $elements,
                static fn (array $el): bool => $el['kind'] === 'system'
            ));
        }

        if ($systems === []) {
            return null;
        }

        if ($systemKey !== null && $systemKey !== '') {
            foreach ($systems as $system) {
                if ($system['key'] === $systemKey) {
                    return $system;
                }
            }
        }

        return $systems[0];
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     * @return array<string, mixed>|null
     */
    public function resolveContainer(array $elements, ?string $containerKey): ?array
    {
        $containers = array_values(array_filter(
            $elements,
            static fn (array $el): bool => $el['kind'] === 'container'
        ));

        if ($containers === []) {
            return null;
        }

        if ($containerKey !== null && $containerKey !== '') {
            foreach ($containers as $container) {
                if ($container['key'] === $containerKey) {
                    return $container;
                }
            }
        }

        return $containers[0];
    }

    /**
     * @param  array<string, mixed>  $el
     */
    protected function elementDeclaration(array $el): string
    {
        $key = $el['key'];
        $name = $this->escape($el['name']);
        $descr = $this->escape($el['description'] ?? '');
        $tech = $this->escape($el['technology'] ?? '');
        $form = strtolower((string) ($el['form'] ?? 'box'));
        $external = ! empty($el['external']);

        return match ($el['kind']) {
            'person' => $this->callWithOptionalArgs('Person', $key, [$name, $descr]),
            'system' => $this->callWithOptionalArgs(
                $this->systemMacro($form, $external),
                $key,
                [$name, $descr]
            ),
            'container' => $this->callWithOptionalArgs(
                $this->containerMacro($form, $external),
                $key,
                [$name, $tech, $descr]
            ),
            'component' => $this->callWithOptionalArgs(
                $this->componentMacro($form, $external),
                $key,
                [$name, $tech, $descr]
            ),
            default => $this->callWithOptionalArgs('System', $key, [$name, $descr]),
        };
    }

    protected function systemMacro(string $form, bool $external): string
    {
        return match ($form) {
            'database' => $external ? 'SystemDb_Ext' : 'SystemDb',
            'queue' => $external ? 'SystemQueue_Ext' : 'SystemQueue',
            default => $external ? 'System_Ext' : 'System',
        };
    }

    protected function containerMacro(string $form, bool $external): string
    {
        return match ($form) {
            'database' => $external ? 'ContainerDb_Ext' : 'ContainerDb',
            'queue' => $external ? 'ContainerQueue_Ext' : 'ContainerQueue',
            default => $external ? 'Container_Ext' : 'Container',
        };
    }

    protected function componentMacro(string $form, bool $external): string
    {
        return match ($form) {
            'database' => $external ? 'ComponentDb_Ext' : 'ComponentDb',
            'queue' => $external ? 'ComponentQueue_Ext' : 'ComponentQueue',
            default => $external ? 'Component_Ext' : 'Component',
        };
    }

    /**
     * Build Element(alias, "a", "b") omitting trailing empty optional strings.
     *
     * @param  list<string>  $stringArgs
     */
    protected function callWithOptionalArgs(string $fn, string $key, array $stringArgs): string
    {
        while ($stringArgs !== [] && end($stringArgs) === '') {
            array_pop($stringArgs);
        }

        $parts = [$key];
        foreach ($stringArgs as $arg) {
            $parts[] = '"'.$arg.'"';
        }

        return $fn.'('.implode(', ', $parts).')';
    }

    /**
     * @param  array<string, mixed>  $rel
     */
    protected function relDeclaration(array $rel): string
    {
        $label = $this->escape($rel['label'] ?? '');
        if ($label === '') {
            $label = 'Relates';
        }
        $tech = $this->escape($rel['technology'] ?? '');
        $fn = $this->relMacro((string) ($rel['direction'] ?? 'rel'));

        if ($tech !== '') {
            return $fn.'('.$rel['from_key'].', '.$rel['to_key'].', "'.$label.'", "'.$tech.'")';
        }

        return $fn.'('.$rel['from_key'].', '.$rel['to_key'].', "'.$label.'")';
    }

    protected function relMacro(string $direction): string
    {
        return match (strtolower($direction)) {
            'up' => 'Rel_U',
            'down' => 'Rel_D',
            'left' => 'Rel_L',
            'right' => 'Rel_R',
            'back' => 'Rel_Back',
            'bi' => 'BiRel',
            default => 'Rel',
        };
    }

    /**
     * @param  array{shapes_per_row: int, boundaries_per_row: int}  $layout
     */
    protected function layoutConfigLine(array $layout): string
    {
        return 'UpdateLayoutConfig($c4ShapeInRow="'.$layout['shapes_per_row'].'", $c4BoundaryInRow="'.$layout['boundaries_per_row'].'")';
    }

    /**
     * @param  array<string, mixed>  $el
     */
    protected function styleLine(array $el): ?string
    {
        $style = is_array($el['style'] ?? null) ? $el['style'] : [];
        $parts = [];

        if (! empty($style['bg_color'])) {
            $parts[] = '$bgColor="'.$this->escape((string) $style['bg_color']).'"';
        }
        if (! empty($style['font_color'])) {
            $parts[] = '$fontColor="'.$this->escape((string) $style['font_color']).'"';
        }
        if (! empty($style['border_color'])) {
            $parts[] = '$borderColor="'.$this->escape((string) $style['border_color']).'"';
        }
        // Mermaid C4 shape args are flaky; colors alone are enough for reliable renders.

        if ($parts === []) {
            return null;
        }

        return 'UpdateElementStyle('.$el['key'].', '.implode(', ', $parts).')';
    }

    /**
     * @param  list<array<string, mixed>>  $relationships
     * @param  list<string>  $keys
     * @return list<array<string, mixed>>
     */
    protected function relsAmong(array $relationships, array $keys): array
    {
        $set = array_fill_keys($keys, true);

        return array_values(array_filter(
            $relationships,
            static fn (array $rel): bool => isset($set[$rel['from_key']], $set[$rel['to_key']])
        ));
    }

    protected function escape(string $value): string
    {
        return str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', ' ', ' '], trim($value));
    }
}
