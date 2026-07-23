<?php

namespace App\Services;

/**
 * Converts a Lane/From/Type/Label/Line-title elements table into Mermaid swimlane-beta text.
 */
class SwimlaneMermaidGenerator
{
    public const TYPES = ['start', 'process', 'decision', 'end'];

    public function __construct(
        protected StateDiagramMermaidGenerator $ids = new StateDiagramMermaidGenerator,
    ) {
    }

    /**
     * @param  list<array{lane?: string|null, from?: string|null, type?: string|null, label?: string|null, line_title?: string|null}>  $elements
     */
    public function generate(?string $title, array $elements, string $direction = 'TB'): string
    {
        // Title belongs in page UI; omit YAML frontmatter.
        unset($title);

        $rows = $this->normalizeElements($elements);
        $direction = strtoupper(trim($direction)) === 'LR' ? 'LR' : 'TB';

        $lines = ['swimlane-beta '.$direction];

        foreach ($this->lanesInOrder($rows) as $lane => $laneRows) {
            $laneId = $this->toNodeId($lane);
            $lines[] = '  subgraph '.$laneId.' ['.$this->sanitizeDisplay($lane).']';

            foreach ($laneRows as $row) {
                $lines[] = '    '.$this->nodeDeclaration($row);
            }

            $lines[] = '  end';
        }

        foreach ($rows as $row) {
            if ($row['from'] === null || $row['from'] === '') {
                continue;
            }

            $fromId = $this->toNodeId($row['from']);
            $toId = $this->toNodeId($row['label']);

            if ($row['line_title'] !== null && $row['line_title'] !== '') {
                $lines[] = '  '.$fromId.' -->|'.$this->sanitizeLineTitle($row['line_title']).'| '.$toId;
            } else {
                $lines[] = '  '.$fromId.' --> '.$toId;
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  list<array{lane?: string|null, from?: string|null, type?: string|null, label?: string|null, line_title?: string|null}>  $elements
     * @return list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null}>
     */
    public function normalizeElements(array $elements): array
    {
        $rows = [];

        foreach ($elements as $row) {
            if (! is_array($row)) {
                continue;
            }

            $lane = trim((string) ($row['lane'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            $type = strtolower(trim((string) ($row['type'] ?? '')));
            $from = trim((string) ($row['from'] ?? ''));
            $lineTitle = trim((string) ($row['line_title'] ?? ''));

            if ($lane === '' && $label === '' && $type === '' && $from === '' && $lineTitle === '') {
                continue;
            }

            if ($lane === '' || $label === '' || ! in_array($type, self::TYPES, true)) {
                continue;
            }

            $rows[] = [
                'lane' => $lane,
                'from' => $from !== '' ? $from : null,
                'type' => $type,
                'label' => $label,
                'line_title' => $lineTitle !== '' ? $lineTitle : null,
            ];
        }

        return $rows;
    }

    public function toNodeId(string $label): string
    {
        return $this->ids->toStateId($label);
    }

    /**
     * @param  list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null}>  $rows
     * @return array<string, list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null}>>
     */
    protected function lanesInOrder(array $rows): array
    {
        $lanes = [];

        foreach ($rows as $row) {
            $lane = $row['lane'];
            if (! array_key_exists($lane, $lanes)) {
                $lanes[$lane] = [];
            }
            $lanes[$lane][] = $row;
        }

        return $lanes;
    }

    /**
     * @param  array{lane: string, from: string|null, type: string, label: string, line_title: string|null}  $row
     */
    protected function nodeDeclaration(array $row): string
    {
        $id = $this->toNodeId($row['label']);
        $label = $this->sanitizeDisplay($row['label']);

        return match ($row['type']) {
            'start', 'end' => $id.'(['.$label.'])',
            'decision' => $id.'{'.$label.'}',
            default => $id.'['.$label.']',
        };
    }

    protected function sanitizeDisplay(string $value): string
    {
        return str_replace(["\n", "\r"], [' ', ' '], trim($value));
    }

    protected function sanitizeLineTitle(string $title): string
    {
        return str_replace(["\n", "\r", '|'], [' ', ' ', ' '], trim($title));
    }
}
