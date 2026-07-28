<?php

namespace App\Services;

/**
 * Exports Architecture model data as Structurizr DSL and workspace JSON.
 */
class StructurizrExporter
{
    public function __construct(
        protected C4ArchitectureNormalizer $normalizer = new C4ArchitectureNormalizer,
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     * @param  list<array<string, mixed>>  $relationships
     */
    public function toDsl(string $workspaceName, array $elements, array $relationships): string
    {
        $elements = $this->normalizer->normalizeElements($elements);
        $relationships = $this->normalizer->normalizeRelationships($relationships);

        $lines = [];
        $lines[] = 'workspace "'.$this->escape($workspaceName).'" {';
        $lines[] = '    model {';

        $byParent = [];
        foreach ($elements as $el) {
            $parent = $el['parent_key'] ?? '';
            $byParent[$parent][] = $el;
        }

        foreach ($byParent[''] ?? [] as $el) {
            if ($el['kind'] === 'group') {
                $pad = '        ';
                $lines[] = $pad.'group "'.$this->escape($el['name']).'" {';
                foreach ($byParent[$el['key']] ?? [] as $child) {
                    $lines = array_merge($lines, $this->dslElementLines($child, $byParent, 3));
                }
                $lines[] = $pad.'}';

                continue;
            }
            $lines = array_merge($lines, $this->dslElementLines($el, $byParent, 2));
        }

        foreach ($relationships as $rel) {
            $label = $rel['label'] ?? 'Uses';
            $tech = $rel['technology'] ?? null;
            $line = '        '.$rel['from_key'].' -> '.$rel['to_key'].' "'.$this->escape((string) $label).'"';
            if ($tech) {
                $line .= ' "'.$this->escape((string) $tech).'"';
            }
            $lines[] = $line;
        }

        $lines[] = '    }';
        $lines[] = '    views {';

        foreach ($elements as $el) {
            if ($el['kind'] !== 'system' || $el['external']) {
                continue;
            }
            $lines[] = '        systemContext '.$el['key'].' {';
            $lines[] = '            include *';
            $lines[] = '            autoLayout';
            $lines[] = '        }';
            $lines[] = '        container '.$el['key'].' {';
            $lines[] = '            include *';
            $lines[] = '            autoLayout';
            $lines[] = '        }';
        }

        foreach ($elements as $el) {
            if ($el['kind'] !== 'container') {
                continue;
            }
            $lines[] = '        component '.$el['key'].' {';
            $lines[] = '            include *';
            $lines[] = '            autoLayout';
            $lines[] = '        }';
        }

        $lines[] = '        styles {';
        foreach ($elements as $el) {
            $styleBlock = $this->dslStyleBlock($el);
            if ($styleBlock !== null) {
                $lines = array_merge($lines, $styleBlock);
            }
        }
        $lines[] = '        }';
        $lines[] = '    }';
        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     * @param  list<array<string, mixed>>  $relationships
     * @return array<string, mixed>
     */
    public function toJson(string $workspaceName, array $elements, array $relationships): array
    {
        $elements = $this->normalizer->normalizeElements($elements);
        $relationships = $this->normalizer->normalizeRelationships($relationships);

        $modelPeople = [];
        $modelSoftwareSystems = [];
        $systemsByKey = [];

        foreach ($elements as $el) {
            if ($el['kind'] === 'person') {
                $modelPeople[] = [
                    'id' => $el['key'],
                    'tags' => 'Element,Person',
                    'name' => $el['name'],
                    'description' => $el['description'] ?? '',
                ];
            }
            if ($el['kind'] === 'system') {
                $systemsByKey[$el['key']] = [
                    'id' => $el['key'],
                    'tags' => $el['external'] ? 'Element,Software System,External' : 'Element,Software System',
                    'name' => $el['name'],
                    'description' => $el['description'] ?? '',
                    'location' => $el['external'] ? 'External' : 'Internal',
                    'containers' => [],
                ];
            }
        }

        foreach ($elements as $el) {
            if ($el['kind'] !== 'container' || $el['parent_key'] === null || ! isset($systemsByKey[$el['parent_key']])) {
                continue;
            }
            $systemsByKey[$el['parent_key']]['containers'][$el['key']] = [
                'id' => $el['key'],
                'tags' => 'Element,Container',
                'name' => $el['name'],
                'description' => $el['description'] ?? '',
                'technology' => $el['technology'] ?? '',
                'components' => [],
            ];
        }

        foreach ($elements as $el) {
            if ($el['kind'] !== 'component' || $el['parent_key'] === null) {
                continue;
            }
            foreach ($systemsByKey as &$system) {
                if (! isset($system['containers'][$el['parent_key']])) {
                    continue;
                }
                $system['containers'][$el['parent_key']]['components'][] = [
                    'id' => $el['key'],
                    'tags' => 'Element,Component',
                    'name' => $el['name'],
                    'description' => $el['description'] ?? '',
                    'technology' => $el['technology'] ?? '',
                ];
            }
            unset($system);
        }

        foreach ($systemsByKey as &$system) {
            $system['containers'] = array_values($system['containers']);
            foreach ($system['containers'] as &$container) {
                // already list
            }
            unset($container);
            $modelSoftwareSystems[] = $system;
        }
        unset($system);

        $rels = [];
        foreach ($relationships as $i => $rel) {
            $rels[] = [
                'id' => (string) ($i + 1),
                'sourceId' => $rel['from_key'],
                'destinationId' => $rel['to_key'],
                'description' => $rel['label'] ?? '',
                'technology' => $rel['technology'] ?? '',
                'tags' => 'Relationship',
            ];
        }

        $views = [
            'systemContextViews' => [],
            'containerViews' => [],
            'componentViews' => [],
            'configuration' => [
                'styles' => [
                    'elements' => [],
                    'relationships' => [],
                ],
            ],
        ];

        foreach ($elements as $el) {
            if ($el['kind'] === 'system' && ! $el['external']) {
                $views['systemContextViews'][] = [
                    'key' => $el['key'].'-context',
                    'softwareSystemId' => $el['key'],
                    'description' => 'System context for '.$el['name'],
                    'elements' => [],
                    'relationships' => [],
                    'automaticLayout' => ['implementation' => 'Graphviz', 'rankDirection' => 'TopBottom'],
                ];
                $views['containerViews'][] = [
                    'key' => $el['key'].'-containers',
                    'softwareSystemId' => $el['key'],
                    'description' => 'Containers for '.$el['name'],
                    'elements' => [],
                    'relationships' => [],
                    'automaticLayout' => ['implementation' => 'Graphviz', 'rankDirection' => 'TopBottom'],
                ];
            }
            if ($el['kind'] === 'container') {
                $views['componentViews'][] = [
                    'key' => $el['key'].'-components',
                    'containerId' => $el['key'],
                    'description' => 'Components for '.$el['name'],
                    'elements' => [],
                    'relationships' => [],
                    'automaticLayout' => ['implementation' => 'Graphviz', 'rankDirection' => 'TopBottom'],
                ];
            }

            $style = is_array($el['style'] ?? null) ? $el['style'] : [];
            if (! empty($style['bg_color']) || ! empty($style['font_color']) || ! empty($style['border_color'])) {
                $views['configuration']['styles']['elements'][] = array_filter([
                    'tag' => $el['key'],
                    'background' => $style['bg_color'] ?? null,
                    'color' => $style['font_color'] ?? null,
                    'stroke' => $style['border_color'] ?? null,
                ]);
            }
        }

        return [
            'id' => 1,
            'name' => $workspaceName,
            'description' => 'Exported from BAssist',
            'model' => [
                'people' => $modelPeople,
                'softwareSystems' => $modelSoftwareSystems,
                'relationships' => $rels,
            ],
            'views' => $views,
        ];
    }

    /**
     * @param  array<string, mixed>  $el
     * @param  array<string, list<array<string, mixed>>>  $byParent
     * @return list<string>
     */
    protected function dslElementLines(array $el, array $byParent, int $indent): array
    {
        $pad = str_repeat('    ', $indent);
        $children = $byParent[$el['key']] ?? [];
        $lines = [];

        if ($el['kind'] === 'person') {
            $lines[] = $pad.$el['key'].' = person "'.$this->escape($el['name']).'"'
                .($el['description'] ? ' "'.$this->escape((string) $el['description']).'"' : '');

            return $lines;
        }

        if ($el['kind'] === 'system') {
            $formTag = $this->formTag($el);
            $needsBlock = $el['external'] || $children !== [] || $formTag !== null;
            $decl = $pad.$el['key'].' = softwareSystem "'.$this->escape($el['name']).'"'
                .($el['description'] ? ' "'.$this->escape((string) $el['description']).'"' : '');
            if (! $needsBlock) {
                $lines[] = $decl;

                return $lines;
            }

            $lines[] = $decl.' {';
            if ($el['external']) {
                $lines[] = $pad.'    tags "External"';
            }
            if ($formTag !== null) {
                $lines[] = $pad.'    tags "'.$formTag.'"';
            }
            foreach ($children as $child) {
                $lines = array_merge($lines, $this->dslElementLines($child, $byParent, $indent + 1));
            }
            $lines[] = $pad.'}';

            return $lines;
        }

        if ($el['kind'] === 'container') {
            $formTag = $this->formTag($el);
            $decl = $pad.$el['key'].' = container "'.$this->escape($el['name']).'"';
            if ($el['description']) {
                $decl .= ' "'.$this->escape((string) $el['description']).'"';
            }
            if ($el['technology']) {
                $decl .= ' "'.$this->escape((string) $el['technology']).'"';
            }
            if ($children !== [] || $formTag !== null) {
                $lines[] = $decl.' {';
                if ($formTag !== null) {
                    $lines[] = $pad.'    tags "'.$formTag.'"';
                }
                foreach ($children as $child) {
                    $lines = array_merge($lines, $this->dslElementLines($child, $byParent, $indent + 1));
                }
                $lines[] = $pad.'}';
            } else {
                $lines[] = $decl;
            }

            return $lines;
        }

        if ($el['kind'] === 'component') {
            $formTag = $this->formTag($el);
            $decl = $pad.$el['key'].' = component "'.$this->escape($el['name']).'"';
            if ($el['description']) {
                $decl .= ' "'.$this->escape((string) $el['description']).'"';
            }
            if ($el['technology']) {
                $decl .= ' "'.$this->escape((string) $el['technology']).'"';
            }
            if ($formTag !== null) {
                $lines[] = $decl.' {';
                $lines[] = $pad.'    tags "'.$formTag.'"';
                $lines[] = $pad.'}';
            } else {
                $lines[] = $decl;
            }

            return $lines;
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $el
     */
    protected function formTag(array $el): ?string
    {
        return match (strtolower((string) ($el['form'] ?? 'box'))) {
            'database' => 'Database',
            'queue' => 'Queue',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $el
     * @return list<string>|null
     */
    protected function dslStyleBlock(array $el): ?array
    {
        $style = is_array($el['style'] ?? null) ? $el['style'] : [];
        if (empty($style['bg_color']) && empty($style['font_color']) && empty($style['border_color'])) {
            return null;
        }

        $pad = '            ';
        $lines = [$pad.'element "'.$el['key'].'" {'];
        if (! empty($style['bg_color'])) {
            $lines[] = $pad.'    background "'.$this->escape((string) $style['bg_color']).'"';
        }
        if (! empty($style['font_color'])) {
            $lines[] = $pad.'    color "'.$this->escape((string) $style['font_color']).'"';
        }
        if (! empty($style['border_color'])) {
            $lines[] = $pad.'    stroke "'.$this->escape((string) $style['border_color']).'"';
        }
        $lines[] = $pad.'}';

        return $lines;
    }

    protected function escape(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], trim($value));
    }
}
