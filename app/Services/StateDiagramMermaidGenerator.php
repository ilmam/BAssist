<?php

namespace App\Services;

/**
 * Converts a From/To/Trigger state table into Mermaid stateDiagram-v2 text.
 *
 * Mermaid terminal symbol is always [*]. User aliases that map to [*] on either side:
 * - `*` / `start` / `end` (case-insensitive)
 * - empty from/to, or [*], [start], [end]
 *
 * Terminals come from transition From/To aliases only (never graph inference).
 * Optional initial/final arguments remain for legacy compose/generate only.
 */
class StateDiagramMermaidGenerator
{
    public const TERMINAL = '[*]';

    public const START_KEYWORD = 'start';

    public const END_KEYWORD = 'end';

    /** User-facing alias shown in the transition editor for [*] endpoints. */
    public const EDITOR_TERMINAL = '*';

    /**
     * @param  list<array{from?: string|null, to?: string|null, trigger?: string|null}>  $transitions
     * @param  list<string>|string|null  $finals
     */
    public function generate(
        ?string $title,
        array $transitions,
        ?string $initial = null,
        array|string|null $finals = null,
    ): string {
        // Title belongs in page UI; omit YAML frontmatter so Mermaid start/end shapes render.
        unset($title);

        $hasLegacyInitial = $initial !== null && trim((string) $initial) !== '';
        $hasLegacyFinals = $finals !== null && (
            (is_string($finals) && trim($finals) !== '') ||
            (is_array($finals) && $finals !== [])
        );

        $rows = $hasLegacyInitial || $hasLegacyFinals
            ? $this->composeFromForm($transitions, $initial, $finals ?? [])
            : $this->normalizeRows($transitions);

        $lines = ['stateDiagram-v2'];

        $aliases = $this->collectAliases($rows);
        foreach ($aliases as $id => $label) {
            $lines[] = '    '.$id.' : '.$label;
        }

        foreach ($rows as $row) {
            $line = '    '.$this->toStateId($row['from']).' --> '.$this->toStateId($row['to']);

            if ($row['trigger'] !== null && $row['trigger'] !== '') {
                $line .= ' : '.$this->sanitizeTrigger($row['trigger']);
            }

            $lines[] = $line;
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Stored transitions → editor rows with `*` for Mermaid [*] terminals.
     *
     * @param  list<array{from?: string|null, to?: string|null, trigger?: string|null}>  $transitions
     * @return list<array{from: string, to: string, trigger: string|null}>
     */
    public function toEditorRows(array $transitions): array
    {
        $rows = [];

        foreach ($this->normalizeRows($transitions) as $row) {
            $rows[] = [
                'from' => $this->isTerminal($row['from']) ? self::EDITOR_TERMINAL : $row['from'],
                'to' => $this->isTerminal($row['to']) ? self::EDITOR_TERMINAL : $row['to'],
                'trigger' => $row['trigger'],
            ];
        }

        return $rows;
    }

    /**
     * Split stored transitions into optional initial/finals + body rows (no [*]).
     *
     * Kept for legacy callers / tests. The UI no longer uses initial/final fields;
     * prefer toEditorRows() so terminals stay in the transition table as `*`.
     *
     * @param  list<array{from?: string|null, to?: string|null, trigger?: string|null}>  $transitions
     * @return array{initial: ?string, finals: list<string>, transitions: list<array{from: string, to: string, trigger: string|null}>}
     */
    public function splitTerminals(array $transitions): array
    {
        $rows = $this->normalizeRows($transitions);
        $initial = null;
        $finals = [];
        $body = [];

        foreach ($rows as $row) {
            $fromTerminal = $this->isTerminal($row['from']);
            $toTerminal = $this->isTerminal($row['to']);
            $hasTrigger = $row['trigger'] !== null && $row['trigger'] !== '';

            if ($fromTerminal && ! $toTerminal) {
                if (! $hasTrigger) {
                    $initial ??= $row['to'];

                    continue;
                }

                $body[] = ['from' => self::START_KEYWORD, 'to' => $row['to'], 'trigger' => $row['trigger']];

                continue;
            }

            if ($toTerminal && ! $fromTerminal) {
                if (! $hasTrigger) {
                    $finals[] = $row['from'];

                    continue;
                }

                $body[] = ['from' => $row['from'], 'to' => self::END_KEYWORD, 'trigger' => $row['trigger']];

                continue;
            }

            if ($fromTerminal && $toTerminal) {
                if ($hasTrigger) {
                    $body[] = $row;
                }

                continue;
            }

            $body[] = $row;
        }

        return [
            'initial' => $initial,
            'finals' => array_values(array_unique($finals)),
            'transitions' => $body,
        ];
    }

    /**
     * Body transitions + optional initial/finals → full transition list including [*].
     *
     * Body keywords `start`/`end` (or blank from/to) become [*] (UML start/end).
     * Optional initial/final fields still add unlabeled terminal edges.
     *
     * @param  list<array{from?: string|null, to?: string|null, trigger?: string|null}>  $bodyTransitions
     * @param  list<string>|string|null  $finals
     * @return list<array{from: string, to: string, trigger: string|null}>
     */
    public function composeFromForm(array $bodyTransitions, ?string $initial = null, array|string|null $finals = []): array
    {
        $body = $this->normalizeRows($bodyTransitions);

        $initial = trim((string) $initial);
        $initial = $initial !== '' ? $initial : null;

        if (is_string($finals)) {
            $finals = preg_split('/\s*,\s*/', $finals, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        $finalList = [];
        foreach (is_array($finals) ? $finals : [] as $value) {
            $value = trim((string) $value);
            if ($value !== '' && ! $this->isTerminal($value)) {
                $finalList[] = $value;
            }
        }
        $finalList = array_values(array_unique($finalList));

        $rows = [];
        if ($initial !== null) {
            $rows[] = ['from' => self::TERMINAL, 'to' => $initial, 'trigger' => null];
        }
        foreach ($body as $row) {
            $rows[] = $row;
        }
        foreach ($finalList as $final) {
            $rows[] = ['from' => $final, 'to' => self::TERMINAL, 'trigger' => null];
        }

        return $rows;
    }

    /**
     * @param  list<array{from?: string|null, to?: string|null, trigger?: string|null}>  $transitions
     * @return list<array{from: string, to: string, trigger: string|null}>
     */
    public function normalizeRows(array $transitions): array
    {
        $rows = [];

        foreach ($transitions as $row) {
            if (! is_array($row)) {
                continue;
            }

            $fromRaw = trim((string) ($row['from'] ?? ''));
            $toRaw = trim((string) ($row['to'] ?? ''));

            // Blank placeholder row — skip. A single blank / start / end endpoint means [*].
            if ($fromRaw === '' && $toRaw === '') {
                continue;
            }

            $from = $this->normalizeEndpoint($fromRaw);
            $to = $this->normalizeEndpoint($toRaw);

            $trigger = trim((string) ($row['trigger'] ?? ''));

            $rows[] = [
                'from' => $from,
                'to' => $to,
                'trigger' => $trigger !== '' ? $trigger : null,
            ];
        }

        return $rows;
    }

    public function isTerminal(string $label): bool
    {
        $normalized = strtolower(trim($label));

        return $normalized === ''
            || in_array($normalized, ['[*]', '*', '[start]', '[end]', self::START_KEYWORD, self::END_KEYWORD], true);
    }

    /**
     * Map an endpoint to [*] when it is a terminal marker.
     *
     * Preferred keywords: source `start`, destination `end` (case-insensitive).
     * Empty and Mermaid aliases ([*], *, [start], [end]) also map to [*].
     * Either keyword on either side maps to [*] so neither becomes a literal Mermaid state.
     */
    public function normalizeEndpoint(string $label): string
    {
        $label = trim($label);

        if ($this->isTerminal($label)) {
            return self::TERMINAL;
        }

        return $label;
    }

    public function normalizeLabel(string $label): string
    {
        return $this->normalizeEndpoint($label);
    }

    /**
     * Mermaid-safe state id. Keeps [*] literal; otherwise strips to [A-Za-z0-9_].
     */
    public function toStateId(string $label): string
    {
        $label = $this->normalizeLabel($label);

        if ($label === self::TERMINAL) {
            return self::TERMINAL;
        }

        $parts = preg_split('/[^A-Za-z0-9]+/', $label, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return 'State';
        }

        $id = '';
        foreach ($parts as $part) {
            $id .= ucfirst(strtolower($part));
        }

        if ($id === '' || ctype_digit($id[0])) {
            $id = 'S'.$id;
        }

        return $id;
    }

    /**
     * @param  list<array{from: string, to: string, trigger: string|null}>  $rows
     * @return array<string, string> id => display label
     */
    protected function collectAliases(array $rows): array
    {
        $aliases = [];

        foreach ($rows as $row) {
            foreach (['from', 'to'] as $key) {
                $label = $row[$key];
                if ($label === self::TERMINAL) {
                    continue;
                }

                $id = $this->toStateId($label);
                if ($id !== $label) {
                    $aliases[$id] = $label;
                }
            }
        }

        ksort($aliases);

        return $aliases;
    }

    protected function sanitizeTrigger(string $trigger): string
    {
        return str_replace(["\n", "\r", ':'], [' ', ' ', ' '], trim($trigger));
    }
}
