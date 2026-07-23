<?php

namespace App\Services;

/**
 * Converts a From/To/Trigger state table into Mermaid stateDiagram-v2 text.
 */
class StateDiagramMermaidGenerator
{
    public const TERMINAL = '[*]';

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

        $rows = $finals !== null || ($initial !== null && trim($initial) !== '')
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
     * Split stored transitions into optional initial/finals + body rows (no [*]).
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

            if ($fromTerminal && ! $toTerminal) {
                $initial ??= $row['to'];

                continue;
            }

            if ($toTerminal && ! $fromTerminal) {
                $finals[] = $row['from'];

                continue;
            }

            if ($fromTerminal && $toTerminal) {
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
     * @param  list<array{from?: string|null, to?: string|null, trigger?: string|null}>  $bodyTransitions
     * @param  list<string>|string|null  $finals
     * @return list<array{from: string, to: string, trigger: string|null}>
     */
    public function composeFromForm(array $bodyTransitions, ?string $initial = null, array|string|null $finals = []): array
    {
        $body = [];
        foreach ($this->normalizeRows($bodyTransitions) as $row) {
            if ($this->isTerminal($row['from']) || $this->isTerminal($row['to'])) {
                continue;
            }
            $body[] = $row;
        }

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

            $from = $this->normalizeLabel((string) ($row['from'] ?? ''));
            $to = $this->normalizeLabel((string) ($row['to'] ?? ''));

            if ($from === '' || $to === '') {
                continue;
            }

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

        return in_array($normalized, ['[*]', '*', '[start]', '[end]'], true);
    }

    public function normalizeLabel(string $label): string
    {
        $label = trim($label);

        if ($this->isTerminal($label)) {
            return self::TERMINAL;
        }

        return $label;
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
