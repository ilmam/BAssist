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

    public const COLOR_MODE_BOTH = 'both';

    public const COLOR_MODE_LANES = 'lanes';

    public const COLOR_MODE_ELEMENTS = 'elements';

    public const COLOR_MODES = [
        self::COLOR_MODE_BOTH,
        self::COLOR_MODE_LANES,
        self::COLOR_MODE_ELEMENTS,
    ];

    /**
     * Lane backgrounds — last/bottom row of Mermaid Studio swimlane picker (pastels).
     *
     * @var array<string, array{fill: string, stroke: string}>
     */
    public const LANE_COLORS = [
        'blue' => ['fill' => '#9ACCE6', 'stroke' => '#5A96B8'],
        'ice' => ['fill' => '#E3F3F3', 'stroke' => '#8AABB0'],
        'mint' => ['fill' => '#BDD8CE', 'stroke' => '#6F9A88'],
        'lime' => ['fill' => '#D6E690', 'stroke' => '#8FA040'],
        'cream' => ['fill' => '#FCFFB0', 'stroke' => '#B8B84A'],
        'peach' => ['fill' => '#FED1A9', 'stroke' => '#D4925A'],
        'rose' => ['fill' => '#FCB4BB', 'stroke' => '#D87884'],
        'pink' => ['fill' => '#FDDDEE', 'stroke' => '#C98AAD'],
        'lilac' => ['fill' => '#E2CAE5', 'stroke' => '#A888B0'],
        'lavender' => ['fill' => '#DAD3F5', 'stroke' => '#8F86C4'],
    ];

    /**
     * Element fills — second row from the bottom of the same picker (lighter than mid-tones).
     *
     * @var array<string, array{fill: string, stroke: string}>
     */
    public const ELEMENT_COLORS = [
        'blue' => ['fill' => '#5EB3DC', 'stroke' => '#4589A8'],
        'ice' => ['fill' => '#D4EDED', 'stroke' => '#81ABAB'],
        'mint' => ['fill' => '#98C3B3', 'stroke' => '#5D8677'],
        'lime' => ['fill' => '#C1D95F', 'stroke' => '#758436'],
        'cream' => ['fill' => '#FCFE8B', 'stroke' => '#A6A843'],
        'peach' => ['fill' => '#FEBA7E', 'stroke' => '#CE8341'],
        'rose' => ['fill' => '#F58A93', 'stroke' => '#D0606C'],
        'pink' => ['fill' => '#FCCCE6', 'stroke' => '#BF739C'],
        'lilac' => ['fill' => '#C9AACE', 'stroke' => '#96759B'],
        'lavender' => ['fill' => '#C1B5E6', 'stroke' => '#8678B1'],
    ];

    public function __construct(
        protected StateDiagramMermaidGenerator $ids = new StateDiagramMermaidGenerator,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function laneColorKeys(): array
    {
        return array_keys(self::LANE_COLORS);
    }

    /**
     * @return list<string>
     */
    public static function elementColorKeys(): array
    {
        return array_keys(self::ELEMENT_COLORS);
    }

    /**
     * @return list<string>
     */
    public static function colorModes(): array
    {
        return self::COLOR_MODES;
    }

    public static function normalizeColorMode(mixed $mode): string
    {
        $mode = strtolower(trim((string) ($mode ?? '')));

        return in_array($mode, self::COLOR_MODES, true) ? $mode : self::COLOR_MODE_BOTH;
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     */
    public function generate(?string $title, array $elements, string $direction = 'TB', string $colorMode = self::COLOR_MODE_BOTH): string
    {
        // Title belongs in page UI; omit YAML frontmatter.
        unset($title);

        $rows = $this->normalizeElements($elements);
        $direction = strtoupper(trim($direction)) === 'LR' ? 'LR' : 'TB';
        $colorMode = self::normalizeColorMode($colorMode);
        $styleLanes = in_array($colorMode, [self::COLOR_MODE_BOTH, self::COLOR_MODE_LANES], true);
        $styleElements = in_array($colorMode, [self::COLOR_MODE_BOTH, self::COLOR_MODE_ELEMENTS], true);

        $lines = ['swimlane-beta '.$direction];
        $laneColors = [];

        foreach ($this->lanesInOrder($rows) as $lane => $laneRows) {
            $laneId = $this->toNodeId($lane);
            // Quoted subgraph titles: unquoted labels with ()/? break Mermaid swimlane-beta.
            $lines[] = '  subgraph '.$laneId.' ['.$this->quotedLabel($lane).']';

            foreach ($laneRows as $row) {
                $lines[] = '    '.$this->nodeDeclaration($row);
            }

            $lines[] = '  end';

            if ($styleLanes && ! array_key_exists($lane, $laneColors)) {
                $laneColors[$lane] = $this->firstLaneColor($laneRows);
            }
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

        if ($styleLanes) {
            foreach ($laneColors as $lane => $colorKey) {
                $style = $this->styleDeclaration($this->toNodeId($lane), $colorKey, self::LANE_COLORS);
                if ($style !== null) {
                    $lines[] = '  '.$style;
                }
            }
        }

        if ($styleElements) {
            foreach ($rows as $row) {
                $style = $this->styleDeclaration(
                    $this->toNodeId($row['label']),
                    $row['element_color'] ?? null,
                    self::ELEMENT_COLORS,
                );
                if ($style !== null) {
                    $lines[] = '  '.$style;
                }
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     * @return list<array{id?: int|null, lane: string, lane_color: string|null, element_color: string|null, from: string|null, type: string, label: string, line_title: string|null, code: string|null, stakeholder_need_id: int|null, number?: int|null}>
     */
    public function normalizeElements(array $elements): array
    {
        $rows = [];

        foreach ($elements as $row) {
            if (! is_array($row)) {
                continue;
            }

            $lane = trim((string) ($row['lane'] ?? ''));
            $laneColor = $this->normalizeColorKey($row['lane_color'] ?? null, self::LANE_COLORS);
            $elementColor = $this->normalizeColorKey($row['element_color'] ?? null, self::ELEMENT_COLORS);
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

            if (
                $lane === '' && $label === '' && $type === '' && $from === '' && $lineTitle === ''
                && $code === null && $stakeholderNeedId === null && $laneColor === null && $elementColor === null
            ) {
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
                'lane_color' => $laneColor,
                'element_color' => $elementColor,
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
     * @param  list<array{id?: int|null, lane: string, lane_color?: string|null, element_color?: string|null, from: string|null, type: string, label: string, line_title: string|null, code: string|null, stakeholder_need_id: int|null, number?: int|null}>  $rows
     * @return list<array{id?: int|null, lane: string, lane_color?: string|null, element_color?: string|null, from: string|null, type: string, label: string, line_title: string|null, code: string|null, stakeholder_need_id: int|null, number?: int|null}>
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
     * @param  list<array{lane: string, lane_color?: string|null, element_color?: string|null, from: string|null, type: string, label: string, line_title: string|null, code?: string|null, stakeholder_need_id?: int|null}>  $rows
     * @return array<string, list<array{lane: string, lane_color?: string|null, element_color?: string|null, from: string|null, type: string, label: string, line_title: string|null, code?: string|null, stakeholder_need_id?: int|null}>>
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
     * @param  list<array{lane_color?: string|null}>  $laneRows
     */
    protected function firstLaneColor(array $laneRows): ?string
    {
        foreach ($laneRows as $row) {
            $color = $this->normalizeColorKey($row['lane_color'] ?? null, self::LANE_COLORS);
            if ($color !== null) {
                return $color;
            }
        }

        return null;
    }

    /**
     * @param  array<string, array{fill: string, stroke: string}>  $palette
     */
    protected function normalizeColorKey(mixed $color, array $palette): ?string
    {
        $key = strtolower(trim((string) ($color ?? '')));
        if ($key === '' || ! array_key_exists($key, $palette)) {
            return null;
        }

        return $key;
    }

    /**
     * @param  array<string, array{fill: string, stroke: string}>  $palette
     */
    protected function styleDeclaration(string $targetId, ?string $colorKey, array $palette): ?string
    {
        $colorKey = $this->normalizeColorKey($colorKey, $palette);
        if ($colorKey === null) {
            return null;
        }

        $swatch = $palette[$colorKey];

        return 'style '.$targetId.' fill:'.$swatch['fill'].',stroke:'.$swatch['stroke'];
    }

    /**
     * @param  array{lane: string, from: string|null, type: string, label: string, line_title: string|null}  $row
     */
    protected function nodeDeclaration(array $row): string
    {
        $id = $this->toNodeId($row['label']);
        $label = $this->quotedLabel($row['label']);

        return match ($row['type']) {
            'start', 'end' => $id.'(['.$label.'])',
            'decision' => $id.'{'.$label.'}',
            default => $id.'['.$label.']',
        };
    }

    /**
     * Quote Mermaid display text so (), ?, and similar characters do not break shapes.
     */
    protected function quotedLabel(string $value): string
    {
        $label = $this->sanitizeDisplay($value);
        $label = str_replace('"', "'", $label);

        return '"'.$label.'"';
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
