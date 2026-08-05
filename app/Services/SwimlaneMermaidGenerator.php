<?php

namespace App\Services;

/**
 * Converts a Lane/From/Type/Label/Line-title elements table into Mermaid swimlane-beta text.
 */
class SwimlaneMermaidGenerator
{
    public const TYPES = ['start', 'process', 'decision', 'end'];

    /** Element types that can link to a Stakeholder Need / be covered by FR|Feature. */
    public const SATISFIABLE_TYPES = ['process', 'decision'];

    public const STEP_CODE_PREFIX = 'PS';

    public function __construct(
        protected StateDiagramMermaidGenerator $ids = new StateDiagramMermaidGenerator,
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $elements
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

            // Mermaid swimlane-beta crashes on self-loops (from === label).
            if ($fromId === $toId) {
                continue;
            }

            if ($row['line_title'] !== null && $row['line_title'] !== '') {
                $lines[] = '  '.$fromId.' -->|'.$this->sanitizeLineTitle($row['line_title']).'| '.$toId;
            } else {
                $lines[] = '  '.$fromId.' --> '.$toId;
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     * @return list<array{id?: int|null, lane: string, from: string|null, type: string, label: string, line_title: string|null, code: string|null, stakeholder_need_id: int|null, number?: int|null}>
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
            $code = $this->normalizeCode($row['code'] ?? null);
            $id = isset($row['id']) && is_numeric($row['id']) ? (int) $row['id'] : null;
            $number = isset($row['number']) && is_numeric($row['number']) ? (int) $row['number'] : null;
            $stakeholderNeedId = isset($row['stakeholder_need_id']) && is_numeric($row['stakeholder_need_id'])
                ? (int) $row['stakeholder_need_id']
                : null;

            if ($lane === '' && $label === '' && $type === '' && $from === '' && $lineTitle === '' && $code === null && $stakeholderNeedId === null) {
                continue;
            }

            if ($lane === '' || $label === '' || ! in_array($type, self::TYPES, true)) {
                continue;
            }

            // Start/end markers do not carry stakeholder-need links.
            if (! in_array($type, self::SATISFIABLE_TYPES, true)) {
                $stakeholderNeedId = null;
            }

            if ($stakeholderNeedId !== null && $stakeholderNeedId < 1) {
                $stakeholderNeedId = null;
            }

            $rows[] = [
                'id' => $id !== null && $id > 0 ? $id : null,
                'lane' => $lane,
                'from' => $from !== '' ? $from : null,
                'type' => $type,
                'label' => $label,
                'line_title' => $lineTitle !== '' ? $lineTitle : null,
                'code' => $code,
                'stakeholder_need_id' => $stakeholderNeedId,
                'number' => $number !== null && $number > 0 ? $number : null,
            ];
        }

        return $this->assignMissingCodes($rows);
    }

    /**
     * @param  list<array{id?: int|null, lane: string, from: string|null, type: string, label: string, line_title: string|null, code: string|null, stakeholder_need_id: int|null, number?: int|null}>  $rows
     * @return list<array{id?: int|null, lane: string, from: string|null, type: string, label: string, line_title: string|null, code: string|null, stakeholder_need_id: int|null, number?: int|null}>
     */
    public function assignMissingCodes(array $rows): array
    {
        $max = 0;

        foreach ($rows as $row) {
            $number = $row['number'] ?? $this->codeNumber($row['code'] ?? null);
            if ($number !== null && $number > $max) {
                $max = $number;
            }
        }

        foreach ($rows as $index => $row) {
            if (($row['code'] ?? null) !== null && $row['code'] !== '') {
                continue;
            }

            $max++;
            $rows[$index]['code'] = self::STEP_CODE_PREFIX.'-'.$max;
            $rows[$index]['number'] = $max;
        }

        return $rows;
    }

    public function toNodeId(string $label): string
    {
        return $this->ids->toStateId($label);
    }

    /**
     * @param  list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null, code?: string|null, stakeholder_need_id?: int|null}>  $rows
     * @return array<string, list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null, code?: string|null, stakeholder_need_id?: int|null}>>
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

    protected function normalizeCode(mixed $code): ?string
    {
        $code = trim((string) ($code ?? ''));
        if ($code === '') {
            return null;
        }

        if (preg_match('/^'.preg_quote(self::STEP_CODE_PREFIX, '/').'-(\d+)$/i', $code, $matches) === 1) {
            return self::STEP_CODE_PREFIX.'-'.(int) $matches[1];
        }

        return $code;
    }

    protected function codeNumber(?string $code): ?int
    {
        if ($code === null || $code === '') {
            return null;
        }

        if (preg_match('/^'.preg_quote(self::STEP_CODE_PREFIX, '/').'-(\d+)$/i', $code, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }
}
