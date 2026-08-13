<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Parses BAssist swimlane-beta Mermaid (generator subset) back into elements rows.
 * Mirrors the client-side parseSwimlaneMermaid helper.
 */
class SwimlaneMermaidParser
{
    /**
     * @return array{direction: string, elements: list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null}>}
     *
     * @throws InvalidArgumentException
     */
    public function parse(string $source): array
    {
        $rawLines = preg_split("/\r\n|\n|\r/", $source) ?: [];
        $direction = null;
        /** @var array<string, array{id: string, label: string, type: string|null, shape: string, lane: string|null}> $nodes */
        $nodes = [];
        /** @var list<array{fromId: string, toId: string, lineTitle: string|null}> $edges */
        $edges = [];
        /** @var list<string> $nodeOrder */
        $nodeOrder = [];
        $currentLane = null;
        $currentLaneLabel = null;
        $inSubgraph = false;

        foreach ($rawLines as $index => $rawLine) {
            $line = trim($rawLine);
            if ($line === '' || str_starts_with($line, '%%')) {
                continue;
            }

            $lineNo = $index + 1;

            if ($direction === null) {
                if (preg_match('/^swimlane-beta(?:\s+(TB|LR))?$/i', $line, $m) === 1) {
                    $direction = strtoupper($m[1] ?? 'TB') === 'LR' ? 'LR' : 'TB';
                    continue;
                }

                throw new InvalidArgumentException(
                    "Line {$lineNo}: expected swimlane-beta TB|LR header."
                );
            }

            if (preg_match('/^(style|classDef|class)\b/i', $line) === 1) {
                continue;
            }

            if (preg_match('/^subgraph\s+([A-Za-z][A-Za-z0-9_]*)\s*\[\s*(?:"([^"]*)"|([^\]]+))\s*\]\s*$/i', $line, $m) === 1) {
                if ($inSubgraph) {
                    throw new InvalidArgumentException("Line {$lineNo}: nested subgraph is not supported.");
                }
                $inSubgraph = true;
                $currentLane = $m[1];
                $currentLaneLabel = $this->labelFromAlternation($m, 2, 3);
                continue;
            }

            if (strcasecmp($line, 'end') === 0) {
                if (! $inSubgraph) {
                    throw new InvalidArgumentException("Line {$lineNo}: unexpected end.");
                }
                $inSubgraph = false;
                $currentLane = null;
                $currentLaneLabel = null;
                continue;
            }

            if (preg_match(
                '/^([A-Za-z][A-Za-z0-9_]*)\s*-->\s*(?:\|([^|]*)\|\s*)?([A-Za-z][A-Za-z0-9_]*)\s*$/',
                $line,
                $m
            ) === 1) {
                $edges[] = [
                    'fromId' => $m[1],
                    'toId' => $m[3],
                    'lineTitle' => $this->nullableTrim($m[2] ?? null),
                ];
                continue;
            }

            $node = $this->parseNodeDeclaration($line);
            if ($node !== null) {
                if (! $inSubgraph || $currentLaneLabel === null) {
                    throw new InvalidArgumentException(
                        "Line {$lineNo}: node declarations must be inside a subgraph lane."
                    );
                }

                $id = $node['id'];
                if ($id === SwimlaneMermaidGenerator::DEFAULT_START_ID) {
                    // Synthetic generator start — never persisted.
                    continue;
                }

                if (! isset($nodes[$id])) {
                    $nodes[$id] = [
                        'id' => $id,
                        'label' => $node['label'],
                        'type' => null,
                        'shape' => $node['shape'],
                        'lane' => $currentLaneLabel,
                    ];
                    $nodeOrder[] = $id;
                }

                continue;
            }

            throw new InvalidArgumentException(
                "Line {$lineNo}: unsupported Mermaid syntax for swimlane import."
            );
        }

        if ($direction === null) {
            throw new InvalidArgumentException('Missing swimlane-beta TB|LR header.');
        }

        if ($inSubgraph) {
            throw new InvalidArgumentException('Unclosed subgraph (missing end).');
        }

        if ($nodes === []) {
            throw new InvalidArgumentException('No lane nodes found to import.');
        }

        foreach ($edges as $edge) {
            $toId = $edge['toId'];
            if ($toId === SwimlaneMermaidGenerator::DEFAULT_START_ID) {
                throw new InvalidArgumentException('Edges into DefaultStart are not supported.');
            }
            if (! isset($nodes[$toId])) {
                throw new InvalidArgumentException(
                    "Edge target \"{$toId}\" is not declared in a lane subgraph."
                );
            }
            $fromId = $edge['fromId'];
            if (
                $fromId !== SwimlaneMermaidGenerator::DEFAULT_START_ID
                && ! isset($nodes[$fromId])
            ) {
                throw new InvalidArgumentException(
                    "Edge source \"{$fromId}\" is not declared in a lane subgraph."
                );
            }
        }

        $this->assignStadiumTypes($nodes, $edges);

        $realIncoming = [];
        $defaultStartTargets = [];
        foreach ($edges as $edge) {
            if ($edge['fromId'] === SwimlaneMermaidGenerator::DEFAULT_START_ID) {
                $defaultStartTargets[$edge['toId']] = true;
                continue;
            }
            $realIncoming[$edge['toId']][] = $edge;
        }

        $elements = [];

        foreach ($nodeOrder as $id) {
            $node = $nodes[$id];
            $hasReal = isset($realIncoming[$id]) && $realIncoming[$id] !== [];
            $needsEmptyFrom = ! $hasReal || isset($defaultStartTargets[$id]);
            if (! $needsEmptyFrom) {
                continue;
            }

            $elements[] = [
                'lane' => $node['lane'],
                'from' => null,
                'type' => $node['type'] ?? 'process',
                'label' => $node['label'],
                'line_title' => null,
            ];
        }

        foreach ($edges as $edge) {
            if ($edge['fromId'] === SwimlaneMermaidGenerator::DEFAULT_START_ID) {
                continue;
            }

            $from = $nodes[$edge['fromId']];
            $to = $nodes[$edge['toId']];
            $elements[] = [
                'lane' => $to['lane'],
                'from' => $from['label'],
                'type' => $to['type'] ?? 'process',
                'label' => $to['label'],
                'line_title' => $edge['lineTitle'],
            ];
        }

        if ($elements === []) {
            throw new InvalidArgumentException('No elements could be derived from Mermaid source.');
        }

        return [
            'direction' => $direction,
            'elements' => $elements,
        ];
    }

    /**
     * @return array{id: string, label: string, shape: string}|null
     */
    protected function parseNodeDeclaration(string $line): ?array
    {
        // Stadium start/end: Id(["Label"]) or Id([Label])
        if (preg_match('/^([A-Za-z][A-Za-z0-9_]*)\(\[\s*(?:"([^"]*)"|([^\]]*?))\s*\]\)\s*$/', $line, $m) === 1) {
            return [
                'id' => $m[1],
                'label' => $this->labelFromAlternation($m, 2, 3),
                'shape' => 'stadium',
            ];
        }

        // Decision: Id{"Label"} or Id{Label}
        if (preg_match('/^([A-Za-z][A-Za-z0-9_]*)\{\s*(?:"([^"]*)"|([^}]*?))\s*\}\s*$/', $line, $m) === 1) {
            return [
                'id' => $m[1],
                'label' => $this->labelFromAlternation($m, 2, 3),
                'shape' => 'diamond',
            ];
        }

        // Process rect: Id["Label"] or Id[Label]
        if (preg_match('/^([A-Za-z][A-Za-z0-9_]*)\[\s*(?:"([^"]*)"|([^\]]*?))\s*\]\s*$/', $line, $m) === 1) {
            return [
                'id' => $m[1],
                'label' => $this->labelFromAlternation($m, 2, 3),
                'shape' => 'rect',
            ];
        }

        return null;
    }

    /**
     * @param  array<int, string>  $m
     */
    protected function labelFromAlternation(array $m, int $quotedGroup, int $plainGroup): string
    {
        if (array_key_exists($quotedGroup, $m) && $m[$quotedGroup] !== '') {
            return $this->unquote($m[$quotedGroup]);
        }

        return $this->unquote(trim((string) ($m[$plainGroup] ?? '')));
    }

    /**
     * @param  array<string, array{id: string, label: string, type: string|null, shape: string, lane: string|null}>  $nodes
     * @param  list<array{fromId: string, toId: string, lineTitle: string|null}>  $edges
     */
    protected function assignStadiumTypes(array &$nodes, array $edges): void
    {
        $outgoing = [];
        $incoming = [];

        foreach ($edges as $edge) {
            if ($edge['fromId'] === SwimlaneMermaidGenerator::DEFAULT_START_ID) {
                // Synthetic — does not count as real incoming for stadium typing of targets
                // beyond empty-from handling elsewhere.
                continue;
            }
            $outgoing[$edge['fromId']] = true;
            $incoming[$edge['toId']] = true;
        }

        foreach ($nodes as $id => &$node) {
            if ($node['shape'] === 'diamond') {
                $node['type'] = 'decision';
                continue;
            }
            if ($node['shape'] === 'rect') {
                $node['type'] = 'process';
                continue;
            }

            // Stadium: start vs end by edge role.
            $hasOut = isset($outgoing[$id]);
            $hasIn = isset($incoming[$id]);
            if ($hasOut && ! $hasIn) {
                $node['type'] = 'start';
            } elseif ($hasIn && ! $hasOut) {
                $node['type'] = 'end';
            } elseif (! $hasIn && ! $hasOut) {
                $node['type'] = 'start';
            } else {
                // Both in and out — uncommon for BPD stadiums; treat as end.
                $node['type'] = 'end';
            }
        }
        unset($node);
    }

    protected function unquote(string $value): string
    {
        $value = trim($value);
        if (
            strlen($value) >= 2
            && str_starts_with($value, '"')
            && str_ends_with($value, '"')
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    protected function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
