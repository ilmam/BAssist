@extends(ui_layout())

@section('main')
    @php
        $queryBase = array_filter([
            'project_id' => $filters['project_id'] ?? null,
            'orphans_only' => ($filters['orphans_only'] ?? false) ? 1 : null,
        ], fn ($v) => $v !== null && $v !== '');

        $exportUrl = route('traceability.export', $queryBase);
        $orphansToggle = $filters['orphans_only']
            ? route('traceability.index', array_filter(['project_id' => $filters['project_id'] ?? null]))
            : route('traceability.index', array_filter([
                'project_id' => $filters['project_id'] ?? null,
                'orphans_only' => 1,
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
        ];
    @endphp

    <x-card title="{{ __('ui.traceability_matrix') }}">
        <x-slot:titleAside>
            <x-help-trigger topic="traceability" />
        </x-slot:titleAside>
        <x-slot:toolbar>
            <div class="flex flex-wrap items-center gap-2">
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

        {{-- Explicit Filter submit: KTSelect fires change on init, so onchange→submit loops forever. --}}
        <form method="GET" action="{{ route('traceability.index') }}" class="mb-5 flex flex-wrap items-end gap-3">
            @if ($filters['orphans_only'] ?? false)
                <input type="hidden" name="orphans_only" value="1">
            @endif

            <div class="flex flex-col gap-1 min-w-[220px]">
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

            <x-button type="submit" color="primary" activeColor="primary">
                {{ __('ui.apply_filters') }}
            </x-button>

            @if ($filters['project_id'] ?? null)
                <a href="{{ route('traceability.index', array_filter(['orphans_only' => ($filters['orphans_only'] ?? false) ? 1 : null])) }}"
                   class="text-sm text-primary underline-offset-2 hover:underline">
                    {{ __('ui.clear_filter') }}
                </a>
            @endif
        </form>

        <div class="mb-5 flex flex-wrap gap-3 text-sm">
            <span class="kt-badge kt-badge-outline">{{ __('ui.matrix_total') }}: {{ $summary['total'] }}</span>
            <span class="kt-badge kt-badge-outline kt-badge-warning">{{ __('ui.matrix_gaps') }}: {{ $summary['gaps'] }}</span>
            @if ($filters['workspace_name'] ?? null)
                <span class="kt-badge kt-badge-outline">{{ __('ui.workspace') }}: {{ $filters['workspace_name'] }}</span>
            @endif
        </div>

        <div class="kt-card-table">
            <div class="kt-table-wrapper">
                <table class="kt-table kt-table-border w-full">
                    <thead>
                        <tr>
                            <th>{{ __('ui.business_objective') }}</th>
                            <th>{{ __('ui.business_need') }}</th>
                            <th>{{ __('ui.stakeholder_need') }}</th>
                            <th>{{ __('ui.solution_requirement') }}</th>
                            <th>{{ __('ui.process_step') }}</th>
                            <th>{{ __('ui.stakeholders') }}</th>
                            <th>{{ __('ui.gaps') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr @class(['is-orphan-row' => $row['has_gap']])>
                                <td>
                                    @if ($row['objective_id'])
                                        <a href="{{ model_modal_path('BusinessObjective', 'view', $row['objective_id']) }}"
                                           class="text-primary hover:underline js-open-modal"
                                           data-modal-url="{{ model_modal_path('BusinessObjective', 'view', $row['objective_id']) }}">
                                            @if (! empty($row['objective_code']))
                                                <span class="text-muted-foreground text-xs me-1">{{ $row['objective_code'] }}</span>
                                            @endif
                                            {{ $row['objective_title'] }}
                                        </a>
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['need_id'])
                                        <a href="{{ model_modal_path('BusinessNeed', 'view', $row['need_id']) }}"
                                           class="text-primary hover:underline js-open-modal"
                                           data-modal-url="{{ model_modal_path('BusinessNeed', 'view', $row['need_id']) }}">
                                            @if (! empty($row['need_code']))
                                                <span class="text-muted-foreground text-xs me-1">{{ $row['need_code'] }}</span>
                                            @endif
                                            {{ $row['need_title'] }}
                                        </a>
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['stakeholder_need_id'])
                                        <a href="{{ model_modal_path('StakeholderNeed', 'view', $row['stakeholder_need_id']) }}"
                                           class="text-primary hover:underline js-open-modal"
                                           data-modal-url="{{ model_modal_path('StakeholderNeed', 'view', $row['stakeholder_need_id']) }}">
                                            @if (! empty($row['stakeholder_need_code']))
                                                <span class="text-muted-foreground text-xs me-1">{{ $row['stakeholder_need_code'] }}</span>
                                            @endif
                                            {{ $row['stakeholder_need_title'] }}
                                        </a>
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if (! empty($row['feature_id']))
                                        <a href="{{ model_modal_path('Feature', 'view', $row['feature_id']) }}"
                                           class="text-primary hover:underline js-open-modal"
                                           data-modal-url="{{ model_modal_path('Feature', 'view', $row['feature_id']) }}">
                                            @if (! empty($row['feature_code']))
                                                <span class="text-muted-foreground text-xs me-1">{{ $row['feature_code'] }}</span>
                                            @endif
                                            {{ $row['feature_title'] }}
                                        </a>
                                        <span class="text-muted-foreground text-xs ms-1">
                                            ({{ __('ui.scenarios') }}: {{ $row['scenarios_count'] ?? 0 }})
                                        </span>
                                    @elseif (! empty($row['functional_requirement_id']))
                                        <a href="{{ model_modal_path('FunctionalRequirement', 'view', $row['functional_requirement_id']) }}"
                                           class="text-primary hover:underline js-open-modal"
                                           data-modal-url="{{ model_modal_path('FunctionalRequirement', 'view', $row['functional_requirement_id']) }}">
                                            @if (! empty($row['functional_requirement_code']))
                                                <span class="text-muted-foreground text-xs me-1">{{ $row['functional_requirement_code'] }}</span>
                                            @endif
                                            {{ $row['functional_requirement_title'] }}
                                        </a>
                                        <span class="text-muted-foreground text-xs ms-1">({{ __('ui.functional_requirement_short') }})</span>
                                    @else
                                        <span class="text-muted-foreground">—</span>
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
                                               data-modal-url="{{ model_modal_path('SwimlaneFlow', 'view', $stepFlowId) }}">
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
                                                <span class="kt-badge kt-badge-sm kt-badge-warning">
                                                    {{ $gapLabels[$gap] ?? $gap }}
                                                </span>
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
    </x-card>
@endsection

@push('styles')
    @include('pages.partials.orphan-row-styles')
@endpush
