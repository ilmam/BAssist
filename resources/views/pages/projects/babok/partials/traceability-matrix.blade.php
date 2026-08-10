@php
    $matrixRows = $matrix['rows'] ?? [];
@endphp

@if ($matrixRows === [])
    <p class="empty">{{ __('ui.matrix_empty') }}</p>
@else
    <h2 class="section-title">{{ __('ui.traceability_matrix') }}</h2>
    <div class="summary">
        <span>{{ __('ui.matrix_total') }}: <strong>{{ $matrix['summary']['total'] ?? 0 }}</strong></span>
        <span>{{ __('ui.matrix_gaps') }}: <strong>{{ $matrix['summary']['gaps'] ?? 0 }}</strong></span>
    </div>
    <table class="matrix">
        <thead>
            <tr>
                <th>{{ __('ui.business_need') }}</th>
                <th>{{ __('ui.business_objective') }}</th>
                <th>{{ __('ui.stakeholder_need') }}</th>
                <th>{{ __('ui.functional_requirement_short') }}</th>
                <th>{{ __('ui.features') }}</th>
                <th>{{ __('ui.process_step') }}</th>
                <th>{{ __('ui.gaps') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($matrixRows as $row)
                <tr @class(['has-gap' => ! empty($row['has_gap'])])>
                    <td>
                        @if (! empty($row['need_code']) || ! empty($row['need_title']))
                            @if (! empty($row['need_code']))
                                <span class="artifact__code">{{ $row['need_code'] }}</span>
                            @endif
                            {{ $row['need_title'] ?? '' }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if (! empty($row['objective_code']) || ! empty($row['objective_title']))
                            @if (! empty($row['objective_code']))
                                <span class="artifact__code">{{ $row['objective_code'] }}</span>
                            @endif
                            {{ $row['objective_title'] ?? '' }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if (! empty($row['stakeholder_need_code']) || ! empty($row['stakeholder_need_title']))
                            @if (! empty($row['stakeholder_need_code']))
                                <span class="artifact__code">{{ $row['stakeholder_need_code'] }}</span>
                            @endif
                            {{ $row['stakeholder_need_title'] ?? '' }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if (! empty($row['functional_requirement_code']) || ! empty($row['functional_requirement_title']))
                            @if (! empty($row['functional_requirement_code']))
                                <span class="artifact__code">{{ $row['functional_requirement_code'] }}</span>
                            @endif
                            {{ $row['functional_requirement_title'] ?? '' }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if (! empty($row['feature_code']) || ! empty($row['feature_title']))
                            @if (! empty($row['feature_code']))
                                <span class="artifact__code">{{ $row['feature_code'] }}</span>
                            @endif
                            {{ $row['feature_title'] ?? '' }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if (! empty($row['design_artifact_code']) || ! empty($row['design_artifact_label']))
                            @if (! empty($row['design_artifact_code']))
                                <span class="artifact__code">{{ $row['design_artifact_code'] }}</span>
                            @endif
                            {{ $row['design_artifact_label'] ?? '' }}
                            @if (! empty($row['design_artifact_flow_title']))
                                <div class="text-muted">{{ $row['design_artifact_flow_title'] }}</div>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @php
                            $gapLabels = collect($row['gaps'] ?? [])->map(function ($gap) {
                                return match ($gap) {
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
                                    default => $gap,
                                };
                            })->all();
                        @endphp
                        {{ $gapLabels !== [] ? implode('; ', $gapLabels) : '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
