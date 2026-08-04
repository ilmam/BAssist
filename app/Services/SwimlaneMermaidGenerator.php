<?php

namespace App\Services;

use App\Support\ProcessStepSatisfyType;

/**
 * Converts a Lane/From/Type/Label/Line-title elements table into Mermaid swimlane-beta text.
 */
class SwimlaneMermaidGenerator
{
    public const TYPES = ['start', 'process', 'decision', 'end'];

    /** Element types that design-satisfy a solution requirement. */
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
     * @return list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null, code: string|null, satisfy_type: string|null, satisfy_id: int|null}>
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
            [$satisfyType, $satisfyId] = $this->normalizeSatisfy($row);

            if ($lane === '' && $label === '' && $type === '' && $from === '' && $lineTitle === '' && $code === null && $satisfyType === null) {
                continue;
            }

            if ($lane === '' || $label === '' || ! in_array($type, self::TYPES, true)) {
                continue;
            }

            // Start/end markers do not satisfy requirements.
            if (! in_array($type, self::SATISFIABLE_TYPES, true)) {
                $satisfyType = null;
                $satisfyId = null;
            }

            $rows[] = [
                'lane' => $lane,
                'from' => $from !== '' ? $from : null,
                'type' => $type,
                'label' => $label,
                'line_title' => $lineTitle !== '' ? $lineTitle : null,
                'code' => $code,
                'satisfy_type' => $satisfyType,
                'satisfy_id' => $satisfyId,
            ];
        }

        return $this->assignMissingCodes($rows);
    }

    /**
     * @param  list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null, code: string|null, satisfy_type: string|null, satisfy_id: int|null}>  $rows
     * @return list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null, code: string|null, satisfy_type: string|null, satisfy_id: int|null}>
     */
    public function assignMissingCodes(array $rows): array
    {
        $max = 0;

        foreach ($rows as $row) {
            $number = $this->codeNumber($row['code'] ?? null);
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
        }

        return $rows;
    }

    public function toNodeId(string $label): string
    {
        return $this->ids->toStateId($label);
    }

    /**
     * @param  list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null, code?: string|null, satisfy_type?: string|null, satisfy_id?: int|null}>  $rows
     * @return array<string, list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null, code?: string|null, satisfy_type?: string|null, satisfy_id?: int|null}>>
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

    /**
     * @param  array<string, mixed>  $row
     * @return array{0: string|null, 1: int|null}
     */
    protected function normalizeSatisfy(array $row): array
    {
        if (array_key_exists('satisfy', $row) && (string) ($row['satisfy'] ?? '') !== '') {
            $decoded = ProcessStepSatisfyType::decode($row['satisfy']);

            return [$decoded['type'], $decoded['id']];
        }

        $type = isset($row['satisfy_type']) ? trim((string) $row['satisfy_type']) : '';
        $id = isset($row['satisfy_id']) && is_numeric($row['satisfy_id']) ? (int) $row['satisfy_id'] : 0;

        if (! ProcessStepSatisfyType::isValid($type) || $id < 1) {
            return [null, null];
        }

        return [$type, $id];
    }
}
