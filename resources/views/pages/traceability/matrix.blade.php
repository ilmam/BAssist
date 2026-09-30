@extends(ui_layout())

@section('main')
    @php
        $queryBase = array_filter([
            'project_id' => $filters['project_id'] ?? null,
            'orphans_only' => ($filters['orphans_only'] ?? false) ? 1 : null,
            'gap' => $filters['gap'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $chipBase = array_filter([
            'project_id' => $filters['project_id'] ?? null,
            'orphans_only' => ($filters['orphans_only'] ?? false) ? 1 : null,
        ], fn ($v) => $v !== null && $v !== '');

        $exportUrl = route('traceability.export', $queryBase);
        $orphansToggle = $filters['orphans_only']
            ? route('traceability.index', array_filter([
                'project_id' => $filters['project_id'] ?? null,
                'gap' => $filters['gap'] ?? null,
            ]))
            : route('traceability.index', array_filter([
                'project_id' => $filters['project_id'] ?? null,
                'orphans_only' => 1,
                'gap' => $filters['gap'] ?? null,
            ]));

        $gapLabels = [
            'missing_objective' => __('ui.gap_missing_objective'),
            'missing_need' => __('ui.gap_missing_need'),
            'missing_stakeholder_need' => __('ui.gap_missing_stakeholder_need'),
            'missing_feature' => __('ui.gap_missing_feature'),
            'missing_scenarios' => __('ui.gap_missing_scenarios'),
            'missing_satisfy' => __('ui.gap_missing_satisfy'),
            'missing_step_stakeholder_need' => __('ui.gap_missing_step_stakeholder_need'),
            'uncovered_process_step' => __('ui.gap_uncovered_process_step'),
            'orphan_objective' => __('ui.gap_orphan_objective'),
            'orphan_stakeholder_need' => __('ui.gap_orphan_stakeholder_need'),
            'orphan_feature' => __('ui.gap_orphan_feature'),
            'orphan_functional_requirement' => __('ui.gap_orphan_functional_requirement'),
            'orphan_non_functional_requirement' => __('ui.gap_orphan_non_functional_requirement'),
        ];
    @endphp

    <x-card title="{{ __('ui.traceability_matrix') }}">
        <x-slot:titleAside>
            <x-help-trigger topic="traceability" />
        </x-slot:titleAside>
        <x-slot:toolbar>
            <div class="flex flex-wrap items-center gap-2">
                <div class="ba-segmented" role="group" aria-label="{{ __('ui.trace_view_mode') }}">
                    <a href="{{ route('traceability.index', $queryBase) }}" @class(['is-active' => $viewMode === 'table']) @if ($viewMode === 'table') aria-current="page" @endif>
                        <i class="ki-filled ki-row-horizontal"></i>{{ __('ui.trace_view_table') }}
                    </a>
                    <a href="{{ route('traceability.index', $queryBase + ['view' => 'graph']) }}" @class(['is-active' => $viewMode === 'graph']) @if ($viewMode === 'graph') aria-current="page" @endif>
                        <i class="ki-filled ki-share"></i>{{ __('ui.trace_view_graph') }}
                    </a>
                </div>
                <a href="{{ $orphansToggle }}"
                   class="{{ ui_btn_classes(($filters['orphans_only'] ?? false) ? 'primary' : 'outline') }}">
                    {{ __('ui.show_gaps') }}
                </a>
                <x-button type="link" href="{{ $exportUrl }}" icon="file-down" color="primary" activeColor="primary">
                    {{ __('ui.export_csv') }}
                </x-button>
            </div>
        </x-slot>

        <p class="text-sm text-muted-foreground mb-5">{{ __('ui.babok_doc_traceability_matrix_note') }}</p>
        <p class="text-xs text-muted-foreground mb-4">{{ __('ui.matrix_focus_hint') }}</p>

        @php
            $traceabilityActive = filled($filters['project_id'] ?? null) ? 1 : 0;
        @endphp

        {{-- Explicit Filter submit: KTSelect fires change on init, so onchange→submit loops forever. --}}
        <x-list-filter-panel
            :active-count="$traceabilityActive"
            :clear-url="$traceabilityActive > 0 ? route('traceability.index', array_filter(['orphans_only' => ($filters['orphans_only'] ?? false) ? 1 : null])) : null"
        >
            <form method="GET" action="{{ route('traceability.index') }}" class="list-filter-panel__form" data-list-filter-form>
                @if ($filters['orphans_only'] ?? false)
                    <input type="hidden" name="orphans_only" value="1">
                @endif
                @if (! empty($filters['gap']))
                    <input type="hidden" name="gap" value="{{ $filters['gap'] }}">
                @endif

                <div class="list-filter-panel__field">
                    <label for="project_id" class="text-sm text-muted-foreground">{{ __('ui.project') }}</label>
                    <select name="project_id" id="project_id" class="kt-select" data-kt-select="true">
                        <option value="">{{ __('ui.all_projects') }}</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((int) ($filters['project_id'] ?? 0) === (int) $project->id)>
                                {{ $project->name }}@if ($project->code) ({{ $project->code }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="list-filter-panel__actions">
                    <x-button type="submit" color="primary" activeColor="primary">
                        {{ __('ui.apply_filters') }}
                    </x-button>
                </div>
            </form>
        </x-list-filter-panel>

        <div class="mb-3 flex flex-wrap gap-2 text-sm">
            <span class="kt-badge kt-badge-outline">{{ __('ui.matrix_total') }}: {{ $summary['total'] }}</span>
            <span class="kt-badge kt-badge-outline kt-badge-warning">{{ __('ui.matrix_gaps') }}: {{ $summary['gaps'] }}</span>
            @if ($filters['workspace_name'] ?? null)
                <span class="kt-badge kt-badge-outline">{{ __('ui.workspace') }}: {{ $filters['workspace_name'] }}</span>
            @endif
        </div>

        @if (($gap_counts ?? []) !== [])
            <div class="mb-5 flex flex-wrap items-center gap-2">
                <a href="{{ route('traceability.index', $chipBase) }}"
                   class="kt-badge kt-badge-sm {{ empty($filters['gap']) ? 'kt-badge-primary' : 'kt-badge-outline' }}">
                    {{ __('ui.matrix_all_rows') }}
                </a>
                @foreach ($gap_counts as $gapKey => $gapCount)
                    <a href="{{ route('traceability.index', $chipBase + ['gap' => $gapKey]) }}"
                       class="kt-badge kt-badge-sm {{ ($filters['gap'] ?? null) === $gapKey ? 'kt-badge-warning' : 'kt-badge-outline kt-badge-warning' }}">
                        {{ $gapLabels[$gapKey] ?? $gapKey }} ({{ $gapCount }})
                    </a>
                @endforeach
            </div>
        @endif

        @if (($coverage ?? []) !== [] && ($coverage[0]['total'] ?? 0) > 0)
            <h4 class="ba-section-label">{{ __('ui.trace_coverage_heading') }}</h4>
            <ol class="ba-spine ba-trace-coverage">
                @foreach ($coverage as $level)
                    @php
                        $pct = $level['pct'];
                        $tone = $pct === null ? 'neutral' : ($pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger'));
                        $href = ($pct !== null && $pct < 100 && $level['gap'] && isset(($gap_counts ?? [])[$level['gap']]))
                            ? route('traceability.index', $chipBase + ['gap' => $level['gap'], 'view' => $viewMode === 'graph' ? 'graph' : null])
                            : null;
                    @endphp
                    <li>
                        <a href="{{ $href ?? '#' }}" class="ba-spine__step ba-spine__step--{{ $tone }}" @if (! $href) aria-disabled="true" tabindex="-1" @endif
                           title="{{ $href ? __('ui.trace_coverage_filter') : '' }}">
                            <span class="ba-spine__label">{{ $level['label'] }}</span>
                            <span class="ba-spine__value">{{ $pct === null ? '—' : $pct.'%' }}</span>
                            <span class="ba-spine__meta">{{ __('ui.readiness_ready_of_total', ['ready' => $level['filled'], 'total' => $level['total']]) }}</span>
                            <span class="ba-spine__bar"><span style="width: {{ $pct ?? 0 }}%"></span></span>
                        </a>
                    </li>
                @endforeach
            </ol>
        @endif

        @if ($viewMode === 'graph')
            @include('pages.traceability.partials.graph', ['graph' => $graph])
        @else
        <div class="kt-card-table">
            <div class="kt-table-wrapper">
                <table class="kt-table kt-table-border w-full" data-traceability-table>
                    <thead>
                        <tr>
                            <th>{{ __('ui.business_need') }}</th>
                            <th>{{ __('ui.business_objective') }}</th>
                            <th>{{ __('ui.stakeholder_need') }}</th>
                            <th>{{ __('ui.solution_requirement') }}</th>
                            <th>{{ __('ui.process_step') }}</th>
                            <th>{{ __('ui.stakeholders') }}</th>
                            <th>{{ __('ui.gaps') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $chainNeed = $row['need_id'] ? 'need:'.$row['need_id'] : '';
                                $chainSn = $row['stakeholder_need_id'] ? 'sn:'.$row['stakeholder_need_id'] : '';
                                $chainFeature = $row['feature_id'] ? 'feature:'.$row['feature_id'] : '';
                                $addFeatureUrl = (! empty($row['stakeholder_need_id']) && in_array('missing_feature', $row['gaps'] ?? [], true) && entity_can('Feature', 'create'))
                                    ? model_modal_path('Feature', 'create').'?'.http_build_query(array_filter([
                                        'project_id' => $row['project_id'] ?? $filters['project_id'] ?? null,
                                        'stakeholder_need_id' => $row['stakeholder_need_id'],
                                    ]))
                                    : null;
                                $addScenarioUrl = (! empty($row['feature_id']) && in_array('missing_scenarios', $row['gaps'] ?? [], true) && entity_can('Scenario', 'create'))
                                    ? model_modal_path('Scenario', 'create').'?'.http_build_query(['feature_id' => $row['feature_id']])
                                    : null;
                                $addStoryUrl = (in_array('missing_stakeholder_need', $row['gaps'] ?? [], true) && entity_can('StakeholderNeed', 'create'))
                                    ? model_modal_path('StakeholderNeed', 'create').'?'.http_build_query(array_filter([
                                        'project_id' => $row['project_id'] ?? $filters['project_id'] ?? null,
                                    ]))
                                    : null;
                            @endphp
                            <tr @class(['is-orphan-row' => $row['has_gap']])
                                data-chain-need="{{ $chainNeed }}"
                                data-chain-sn="{{ $chainSn }}"
                                data-chain-feature="{{ $chainFeature }}">
                                <td>
                                    @if ($row['need_id'])
                                        <a href="{{ model_modal_path('BusinessNeed', 'view', $row['need_id']) }}"
                                           class="text-primary hover:underline js-open-modal"
                                           data-modal-url="{{ model_modal_path('BusinessNeed', 'view', $row['need_id']) }}"
                                           data-modal-nav="off">
                                            @if (! empty($row['need_code']))
                                                <span class="text-muted-foreground text-xs me-1">{{ $row['need_code'] }}</span>
                                            @endif
                                            {{ $row['need_title'] }}
                                        </a>
                                    @else
                                        @if (in_array('missing_need', $row['gaps'] ?? [], true))
                                            <x-status-badge tone="warning">{{ __('ui.trace_cell_missing') }}</x-status-badge>
                                        @else
                                            <span class="text-muted-foreground">—</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if ($row['objective_id'])
                                        <a href="{{ model_modal_path('BusinessObjective', 'view', $row['objective_id']) }}"
                                           class="text-primary hover:underline js-open-modal"
                                           data-modal-url="{{ model_modal_path('BusinessObjective', 'view', $row['objective_id']) }}"
                                           data-modal-nav="off">
                                            @if (! empty($row['objective_code']))
                                                <span class="text-muted-foreground text-xs me-1">{{ $row['objective_code'] }}</span>
                                            @endif
                                            {{ $row['objective_title'] }}
                                        </a>
                                    @else
                                        @if (in_array('missing_objective', $row['gaps'] ?? [], true))
                                            <x-status-badge tone="warning">{{ __('ui.trace_cell_missing') }}</x-status-badge>
                                        @else
                                            <span class="text-muted-foreground">—</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if ($row['stakeholder_need_id'])
                                        <a href="{{ model_modal_path('StakeholderNeed', 'view', $row['stakeholder_need_id']) }}"
                                           class="text-primary hover:underline js-open-modal"
                                           data-modal-url="{{ model_modal_path('StakeholderNeed', 'view', $row['stakeholder_need_id']) }}"
                                           data-modal-nav="off">
                                            @if (! empty($row['stakeholder_need_code']))
                                                <span class="text-muted-foreground text-xs me-1">{{ $row['stakeholder_need_code'] }}</span>
                                            @endif
                                            {{ $row['stakeholder_need_title'] }}
                                        </a>
                                    @else
                                        @if (in_array('missing_stakeholder_need', $row['gaps'] ?? [], true))
                                            <x-status-badge tone="warning">{{ __('ui.trace_cell_missing') }}</x-status-badge>
                                        @else
                                            <span class="text-muted-foreground">—</span>
                                        @endif
                                        @if ($addStoryUrl)
                                            <a href="{{ $addStoryUrl }}"
                                               class="text-xs text-primary hover:underline js-open-modal ms-1"
                                               data-modal-url="{{ $addStoryUrl }}"
                                               data-modal-nav="off">{{ __('ui.matrix_add_story') }}</a>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if (! empty($row['feature_id']))
                                        <a href="{{ model_modal_path('Feature', 'view', $row['feature_id']) }}"
                                           class="text-primary hover:underline js-open-modal"
                                           data-modal-url="{{ model_modal_path('Feature', 'view', $row['feature_id']) }}"
                                           data-modal-nav="off">
                                            @if (! empty($row['feature_code']))
                                                <span class="text-muted-foreground text-xs me-1">{{ $row['feature_code'] }}</span>
                                            @endif
                                            {{ $row['feature_title'] }}
                                        </a>
                                        <span class="text-muted-foreground text-xs ms-1">
                                            ({{ __('ui.scenarios') }}: {{ $row['scenarios_count'] ?? 0 }})
                                        </span>
                                        @if (! empty($row['scenario_id']))
                                            <div class="text-xs text-muted-foreground mt-1">
                                                {{ __('ui.scenario_covers_need') }}:
                                                <a href="{{ model_modal_path('Scenario', 'view', $row['scenario_id']) }}"
                                                   class="text-primary hover:underline js-open-modal"
                                                   data-modal-url="{{ model_modal_path('Scenario', 'view', $row['scenario_id']) }}"
                                                   data-modal-nav="off">
                                                    {{ $row['scenario_title'] }}
                                                </a>
                                            </div>
                                        @endif
                                        @if ($addScenarioUrl)
                                            <a href="{{ $addScenarioUrl }}"
                                               class="text-xs text-primary hover:underline js-open-modal ms-1"
                                               data-modal-url="{{ $addScenarioUrl }}"
                                               data-modal-nav="off">{{ __('ui.add_scenario') }}</a>
                                        @endif
                                    @elseif (! empty($row['functional_requirement_id']))
                                        <a href="{{ model_modal_path('FunctionalRequirement', 'view', $row['functional_requirement_id']) }}"
                                           class="text-primary hover:underline js-open-modal"
                                           data-modal-url="{{ model_modal_path('FunctionalRequirement', 'view', $row['functional_requirement_id']) }}"
                                           data-modal-nav="off">
                                            @if (! empty($row['functional_requirement_code']))
                                                <span class="text-muted-foreground text-xs me-1">{{ $row['functional_requirement_code'] }}</span>
                                            @endif
                                            {{ $row['functional_requirement_title'] }}
                                        </a>
                                        <span class="text-muted-foreground text-xs ms-1">({{ __('ui.functional_requirement_short') }})</span>
                                    @elseif (! empty($row['non_functional_requirement_id']))
                                        <a href="{{ model_modal_path('NonFunctionalRequirement', 'view', $row['non_functional_requirement_id']) }}"
                                           class="text-primary hover:underline js-open-modal"
                                           data-modal-url="{{ model_modal_path('NonFunctionalRequirement', 'view', $row['non_functional_requirement_id']) }}"
                                           data-modal-nav="off">
                                            @if (! empty($row['non_functional_requirement_code']))
                                                <span class="text-muted-foreground text-xs me-1">{{ $row['non_functional_requirement_code'] }}</span>
                                            @endif
                                            {{ $row['non_functional_requirement_title'] }}
                                        </a>
                                        <span class="text-muted-foreground text-xs ms-1">({{ __('ui.non_functional_requirement_short') }})</span>
                                    @elseif (! empty($row['scenario_id']))
                                        <a href="{{ model_modal_path('Scenario', 'view', $row['scenario_id']) }}"
                                           class="text-primary hover:underline js-open-modal"
                                           data-modal-url="{{ model_modal_path('Scenario', 'view', $row['scenario_id']) }}"
                                           data-modal-nav="off">
                                            {{ $row['scenario_title'] }}
                                        </a>
                                        <span class="text-muted-foreground text-xs ms-1">({{ __('ui.scenario_covers_need') }})</span>
                                    @else
                                        @if (! empty($row['deferred_this_release']))
                                            <span class="text-muted-foreground">{{ __('ui.matrix_deferred_this_release') }}</span>
                                        @else
                                            <span class="text-muted-foreground">—</span>
                                            @if ($addFeatureUrl)
                                                <a href="{{ $addFeatureUrl }}"
                                                   class="text-xs text-primary hover:underline js-open-modal ms-1"
                                                   data-modal-url="{{ $addFeatureUrl }}"
                                                   data-modal-nav="off">{{ __('ui.matrix_add_feature') }}</a>
                                            @endif
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $stepCode = $row['process_step_code'] ?? $row['design_artifact_code'] ?? null;
                                        $stepLabel = $row['process_step_label'] ?? $row['design_artifact_label'] ?? null;
                                        $stepFlowId = $row['process_step_flow_id'] ?? $row['design_artifact_flow_id'] ?? null;
                                        $stepFlowTitle = $row['process_step_flow_title'] ?? $row['design_artifact_flow_title'] ?? null;
                                    @endphp
                                    @if (! empty($stepCode) || ! empty($stepLabel))
                                        @if (! empty($stepFlowId))
                                            <a href="{{ model_modal_path('SwimlaneFlow', 'view', $stepFlowId) }}"
                                               class="text-primary hover:underline js-open-modal"
                                               data-modal-url="{{ model_modal_path('SwimlaneFlow', 'view', $stepFlowId) }}"
                                               data-modal-nav="off">
                                                @if (! empty($stepCode))
                                                    <span class="text-muted-foreground text-xs me-1">{{ $stepCode }}</span>
                                                @endif
                                                {{ $stepLabel }}
                                            </a>
                                        @else
                                            @if (! empty($stepCode))
                                                <span class="text-muted-foreground text-xs me-1">{{ $stepCode }}</span>
                                            @endif
                                            {{ $stepLabel }}
                                        @endif
                                        @if (! empty($stepFlowTitle))
                                            <div class="text-muted-foreground text-xs mt-0.5">{{ $stepFlowTitle }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if (! empty($row['stakeholder_names']))
                                        {{ implode(', ', $row['stakeholder_names']) }}
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['has_gap'])
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($row['gaps'] as $gap)
                                                <a href="{{ route('traceability.index', $chipBase + ['gap' => $gap]) }}"
                                                   class="kt-badge kt-badge-sm kt-badge-warning">
                                                    {{ $gapLabels[$gap] ?? $gap }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-secondary-foreground">{{ __('ui.matrix_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </x-card>
@endsection

@push('styles')
    @include('pages.partials.orphan-row-styles')
    <style>
        [data-traceability-table] thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: var(--background, #fff);
        }
        [data-traceability-table] tbody tr {
            cursor: pointer;
        }
        [data-traceability-table] tbody tr.is-chain-focus td {
            background-color: color-mix(in srgb, var(--primary, #1b84ff) 10%, transparent);
        }
        [data-traceability-table].is-focusing tbody tr.is-chain-dim td {
            opacity: 0.4;
        }
    </style>
@endpush

@push('scripts')
<script>
    (function () {
        const table = document.querySelector('[data-traceability-table]');
        if (!table) {
            return;
        }

        function chainKeys(row) {
            return ['need', 'sn', 'feature']
                .map((key) => row.getAttribute('data-chain-' + key) || '')
                .filter(Boolean);
        }

        function related(a, b) {
            const keysA = chainKeys(a);
            const keysB = chainKeys(b);
            return keysA.some((key) => keysB.includes(key));
        }

        table.addEventListener('click', function (event) {
            if (event.target.closest('a, button')) {
                return;
            }

            const row = event.target.closest('tbody tr[data-chain-need], tbody tr[data-chain-sn], tbody tr[data-chain-feature]');
            if (!row) {
                return;
            }

            const rows = table.querySelectorAll('tbody tr');
            const already = row.classList.contains('is-chain-focus');
            table.classList.toggle('is-focusing', !already);

            rows.forEach((candidate) => {
                candidate.classList.remove('is-chain-focus', 'is-chain-dim');
                if (already) {
                    return;
                }
                if (related(row, candidate)) {
                    candidate.classList.add('is-chain-focus');
                } else {
                    candidate.classList.add('is-chain-dim');
                }
            });
        });
    })();
</script>
@endpush
