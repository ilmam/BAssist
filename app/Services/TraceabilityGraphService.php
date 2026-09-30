<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Turns traceability matrix rows into (a) per-level coverage and (b) a Mermaid
 * flowchart of the need spine with gaps drawn as dashed amber nodes.
 */
class TraceabilityGraphService
{
    /** Above this the graph stops being readable; the view asks for a project filter. */
    public const MAX_NODES = 180;

    /**
     * Share of matrix rows that have each spine level filled.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{key: string, label: string, filled: int, total: int, pct: int|null, gap: string|null}>
     */
    public function coverage(array $rows): array
    {
        $rows = array_values(array_filter($rows, fn (array $row) => empty($row['deferred_this_release'])));
        $total = count($rows);

        $levels = [
            ['need', __('ui.business_need'), fn ($r) => ! empty($r['need_id']), 'missing_need'],
            ['objective', __('ui.business_objective'), fn ($r) => ! empty($r['objective_id'] ?? $r['objective_code'] ?? null), 'missing_objective'],
            ['stakeholder_need', __('ui.stakeholder_need'), fn ($r) => ! empty($r['stakeholder_need_id']), 'missing_stakeholder_need'],
            ['solution', __('ui.solution_requirement'), fn ($r) => ! empty($r['feature_id']) || ! empty($r['functional_requirement_id']) || ! empty($r['non_functional_requirement_id']), 'missing_feature'],
            ['proof', __('ui.trace_coverage_proof'), fn ($r) => ! empty($r['scenario_id']) || (int) ($r['scenarios_count'] ?? 0) > 0 || ! empty($r['functional_requirement_id']) || ! empty($r['non_functional_requirement_id']), 'missing_scenarios'],
        ];

        return array_map(function (array $level) use ($rows, $total): array {
            [$key, $label, $test, $gap] = $level;
            $filled = count(array_filter($rows, $test));

            return [
                'key' => $key,
                'label' => $label,
                'filled' => $filled,
                'total' => $total,
                'pct' => $total > 0 ? (int) round(100 * $filled / $total) : null,
                'gap' => $gap,
            ];
        }, $levels);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{mermaid: string, links: array<string, string>, nodes: int, too_large: bool}
     */
    public function graph(array $rows): array
    {
        $nodes = [];
        $edges = [];
        $links = [];

        $add = function (string $id, string $class, string $label, ?string $model, ?int $key) use (&$nodes, &$links): void {
            if (! isset($nodes[$id])) {
                $nodes[$id] = [$class, $label];
                if ($model !== null && $key !== null && entity_can($model, 'view')) {
                    $links[$id] = model_modal_path($model, 'view', $key);
                }
            }
        };
        $edge = function (?string $from, ?string $to, bool $gap = false) use (&$edges): void {
            if ($from !== null && $to !== null && $from !== $to) {
                $edges[$from.'|'.$to] = [$from, $to, $gap];
            }
        };

        foreach ($rows as $row) {
            $gaps = $row['gaps'] ?? [];
            $bn = $bo = $sn = $sol = null;

            if (! empty($row['need_id'])) {
                $bn = 'BN'.$row['need_id'];
                $add($bn, 'need', $this->label($row['need_code'] ?? null, $row['need_title'] ?? ''), 'BusinessNeed', (int) $row['need_id']);
            }
            $objectiveId = $row['objective_id'] ?? null;
            if (! empty($objectiveId)) {
                $bo = 'BO'.$objectiveId;
                $add($bo, 'objective', $this->label($row['objective_code'] ?? null, $row['objective_title'] ?? ''), 'BusinessObjective', (int) $objectiveId);
            } elseif ($bn !== null && in_array('missing_objective', $gaps, true)) {
                $bo = 'GAPBO'.$row['need_id'];
                $add($bo, 'gap', __('ui.trace_graph_missing_objective'), null, null);
            }
            if (! empty($row['stakeholder_need_id'])) {
                $sn = 'SN'.$row['stakeholder_need_id'];
                $add($sn, 'story', $this->label($row['stakeholder_need_code'] ?? null, $row['stakeholder_need_title'] ?? ''), 'StakeholderNeed', (int) $row['stakeholder_need_id']);
            } elseif ($bo !== null && in_array('missing_stakeholder_need', $gaps, true)) {
                $sn = 'GAPSN'.$objectiveId;
                $add($sn, 'gap', __('ui.trace_graph_missing_story'), null, null);
            }

            $edge($bn, $bo, str_starts_with((string) $bo, 'GAP'));
            $edge($bo, $sn, str_starts_with((string) $sn, 'GAP'));

            foreach ([
                ['feature', 'FE', 'Feature'],
                ['functional_requirement', 'FR', 'FunctionalRequirement'],
                ['non_functional_requirement', 'NFR', 'NonFunctionalRequirement'],
            ] as [$prefix, $idPrefix, $model]) {
                if (! empty($row[$prefix.'_id'])) {
                    $sol = $idPrefix.$row[$prefix.'_id'];
                    $add($sol, 'solution', $this->label($row[$prefix.'_code'] ?? null, $row[$prefix.'_title'] ?? ''), $model, (int) $row[$prefix.'_id']);
                    $edge($sn, $sol);

                    if ($prefix === 'feature') {
                        $count = (int) ($row['scenarios_count'] ?? 0);
                        if ($count > 0) {
                            $sc = 'SC'.$row['feature_id'];
                            $add($sc, 'proof', trans_choice('ui.lineage_count_scenarios', $count, ['count' => $count]), 'Feature', (int) $row['feature_id']);
                            $edge($sol, $sc);
                        } elseif (in_array('missing_scenarios', $gaps, true)) {
                            $sc = 'GAPSC'.$row['feature_id'];
                            $add($sc, 'gap', __('ui.trace_graph_missing_scenarios'), null, null);
                            $edge($sol, $sc, true);
                        }
                    }
                }
            }

            if ($sn === null && $sol !== null) {
                $orphanGap = 'GAPUP'.$sol;
                $add($orphanGap, 'gap', __('ui.trace_graph_missing_story'), null, null);
                $edge($orphanGap, $sol, true);
            }

            if ($sn !== null && ! str_starts_with($sn, 'GAP') && in_array('missing_feature', $gaps, true)
                && empty($row['feature_id']) && empty($row['functional_requirement_id']) && empty($row['non_functional_requirement_id'])) {
                $gapId = 'GAPSOL'.$row['stakeholder_need_id'];
                $add($gapId, 'gap', __('ui.trace_graph_missing_solution'), null, null);
                $edge($sn, $gapId, true);
            }
        }

        $lines = ['flowchart LR'];
        foreach ($nodes as $id => [$class, $label]) {
            $shape = $class === 'gap' ? '(["%s"])' : '["%s"]';
            $lines[] = '    '.$id.sprintf($shape, $label).':::'.$class;
        }
        foreach ($edges as [$from, $to, $gap]) {
            $lines[] = '    '.$from.($gap ? ' -.-> ' : ' --> ').$to;
        }
        $lines[] = '    classDef need fill:#eef2ff,stroke:#6366f1,color:#1e1b4b';
        $lines[] = '    classDef objective fill:#ecfeff,stroke:#0891b2,color:#083344';
        $lines[] = '    classDef story fill:#eff6ff,stroke:#1b84ff,color:#0b2545';
        $lines[] = '    classDef solution fill:#f0fdf4,stroke:#16a34a,color:#052e16';
        $lines[] = '    classDef proof fill:#f7fee7,stroke:#65a30d,color:#1a2e05';
        $lines[] = '    classDef gap fill:#fffbeb,stroke:#d97706,color:#78350f,stroke-dasharray:5 4';

        return [
            'mermaid' => implode("\n", $lines),
            'links' => $links,
            'nodes' => count($nodes),
            'too_large' => count($nodes) > self::MAX_NODES,
        ];
    }

    protected function label(?string $code, string $title): string
    {
        $escape = fn (string $text): string => str_replace(
            ['&', '"', '[', ']', '{', '}', '|', '<', '>'],
            ['#amp;', '#quot;', '#91;', '#93;', '#123;', '#125;', '#124;', '#lt;', '#gt;'],
            $text,
        );
        $title = $escape(Str::limit(trim($title), 42));

        return $code ? $escape($code).'<br/>'.$title : $title;
    }
}
