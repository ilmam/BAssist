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
            'orphan_objective' => __('ui.gap_orphan_objective'),
            'orphan_stakeholder_need' => __('ui.gap_orphan_stakeholder_need'),
            'orphan_feature' => __('ui.gap_orphan_feature'),
        ];
    @endphp

    <x-card title="{{ __('ui.traceability_matrix') }}">
        <x-slot:toolbar>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $orphansToggle }}"
                   class="kt-btn kt-btn-sm {{ ($filters['orphans_only'] ?? false) ? 'kt-btn-warning' : 'kt-btn-outline' }}">
                    {{ __('ui.show_gaps') }}
                </a>
                <x-button type="link" href="{{ $exportUrl }}" icon="file-down" color="primary" activeColor="primary">
                    {{ __('ui.export_csv') }}
                </x-button>
            </div>
        </x-slot>

        <form method="GET" action="{{ route('traceability.index') }}" class="mb-5 flex flex-wrap items-end gap-3">
            @if ($filters['orphans_only'] ?? false)
                <input type="hidden" name="orphans_only" value="1">
            @endif

            <div class="flex flex-col gap-1 min-w-[220px]">
                <label for="project_id" class="text-sm text-muted-foreground">{{ __('ui.project') }}</label>
                <select name="project_id" id="project_id" class="kt-select" data-kt-select="true" onchange="this.form.submit()">
                    <option value="">{{ __('ui.all_projects') }}</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((int) ($filters['project_id'] ?? 0) === (int) $project->id)>
                            {{ $project->name }}@if ($project->code) ({{ $project->code }})@endif
                        </option>
                    @endforeach
                </select>
            </div>

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
                            <th>{{ __('ui.project') }}</th>
                            <th>{{ __('ui.business_objective') }}</th>
                            <th>{{ __('ui.business_need') }}</th>
                            <th>{{ __('ui.stakeholder_need') }}</th>
                            <th>{{ __('ui.feature') }}</th>
                            <th>{{ __('ui.stakeholders') }}</th>
                            <th>{{ __('ui.gaps') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr @class(['is-orphan-row' => $row['has_gap']])>
                                <td class="font-medium whitespace-nowrap">
                                    {{ $row['project_name'] ?? '—' }}
                                    @if (! empty($row['project_code']))
                                        <span class="text-muted-foreground text-xs">{{ $row['project_code'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['objective_id'])
                                        <a href="{{ model_route('BusinessObjective', 'show', $row['objective_id']) }}"
                                           class="text-primary hover:underline">
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
                                        <a href="{{ model_route('BusinessNeed', 'show', $row['need_id']) }}"
                                           class="text-primary hover:underline">
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
                                        <a href="{{ model_route('StakeholderNeed', 'show', $row['stakeholder_need_id']) }}"
                                           class="text-primary hover:underline">
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
                                        <a href="{{ model_route('Feature', 'show', $row['feature_id']) }}"
                                           class="text-primary hover:underline">
                                            @if (! empty($row['feature_code']))
                                                <span class="text-muted-foreground text-xs me-1">{{ $row['feature_code'] }}</span>
                                            @endif
                                            {{ $row['feature_title'] }}
                                        </a>
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
