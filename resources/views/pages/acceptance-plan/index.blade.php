@extends(ui_layout())

@section('main')
    @php
        $queryBase = array_filter([
            'project_id' => $filters['project_id'] ?? null,
            'feature_id' => $filters['feature_id'] ?? null,
            'type' => $filters['type'] ?? null,
            'stakeholder_need_id' => $filters['stakeholder_need_id'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $exportCsvUrl = route('acceptance-plan.export', array_merge($queryBase, ['format' => 'csv']));
        $exportMdUrl = route('acceptance-plan.export', array_merge($queryBase, ['format' => 'md']));
    @endphp

    <x-card title="{{ __('ui.acceptance_plan') }}">
        <x-slot:titleAside>
            <x-help-trigger topic="acceptance_plan" />
        </x-slot:titleAside>
        <x-slot:toolbar>
            <div class="flex flex-wrap items-center gap-2">
                <x-button type="link" href="{{ $exportMdUrl }}" icon="file-down" color="ghost" size="sm" activeColor="primary">
                    {{ __('ui.export_markdown') }}
                </x-button>
                <x-button type="link" href="{{ $exportCsvUrl }}" icon="file-down" color="primary" activeColor="primary">
                    {{ __('ui.export_csv') }}
                </x-button>
            </div>
        </x-slot>

        <p class="text-sm text-muted-foreground mb-5">{{ __('ui.babok_doc_acceptance_criteria_note') }}</p>

        {{-- Explicit Filter submit: KTSelect fires change on init, so onchange→submit loops forever. --}}
        <form method="GET" action="{{ route('acceptance-plan.index') }}" class="mb-5 flex flex-wrap items-end gap-3">
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

            <div class="flex flex-col gap-1 min-w-[220px]">
                <label for="feature_id" class="text-sm text-muted-foreground">{{ __('ui.feature') }}</label>
                <select name="feature_id" id="feature_id" class="kt-select" data-kt-select="true"
                        @disabled(empty($features) || $features->isEmpty())>
                    <option value="">{{ __('ui.all_features') }}</option>
                    @foreach ($features as $feature)
                        <option value="{{ $feature->id }}" @selected((int) ($filters['feature_id'] ?? 0) === (int) $feature->id)>
                            @if ($feature->code)
                                {{ $feature->code }} —
                            @endif
                            {{ $feature->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1 min-w-[180px]">
                <label for="type" class="text-sm text-muted-foreground">{{ __('ui.type') }}</label>
                <select name="type" id="type" class="kt-select" data-kt-select="true">
                    <option value="">{{ __('ui.all_types') }}</option>
                    <option value="happy_path" @selected(($filters['type'] ?? null) === 'Happy Path')>
                        {{ __('ui.happy_path') }}
                    </option>
                    <option value="edge_case" @selected(($filters['type'] ?? null) === 'Edge Case')>
                        {{ __('ui.edge_case') }}
                    </option>
                </select>
            </div>

            <x-button type="submit" color="primary" activeColor="primary">
                {{ __('ui.apply_filters') }}
            </x-button>

            @if (($filters['project_id'] ?? null) || ($filters['feature_id'] ?? null) || ($filters['type'] ?? null))
                <a href="{{ route('acceptance-plan.index', ['clear_project' => 1]) }}"
                   class="text-sm text-primary underline-offset-2 hover:underline">
                    {{ __('ui.clear_filter') }}
                </a>
            @endif
        </form>

        <div class="mb-5 flex flex-wrap gap-3 text-sm">
            <span class="kt-badge kt-badge-outline">{{ __('ui.matrix_total') }}: {{ $summary['total'] }}</span>
            <span class="kt-badge kt-badge-outline">{{ __('ui.acceptance_plan_source_bdd') }}: {{ $summary['bdd'] ?? 0 }}</span>
            <span class="kt-badge kt-badge-outline">{{ __('ui.acceptance_plan_source_fr') }}: {{ $summary['fr'] ?? 0 }}</span>
            <span class="kt-badge kt-badge-outline">{{ __('ui.happy_path') }}: {{ $summary['happy_path'] }}</span>
            <span class="kt-badge kt-badge-outline">{{ __('ui.edge_case') }}: {{ $summary['edge_case'] }}</span>
            @if ($filters['workspace_name'] ?? null)
                <span class="kt-badge kt-badge-outline">{{ __('ui.workspace') }}: {{ $filters['workspace_name'] }}</span>
            @endif
        </div>

        <div class="kt-card-table">
            <div class="kt-table-wrapper">
                <table class="kt-table kt-table-border w-full">
                    <thead>
                        <tr>
                            <th>{{ __('ui.test_id') }}</th>
                            <th>{{ __('ui.acceptance_plan_requirement') }}</th>
                            <th>{{ __('ui.acceptance_plan_rule') }}</th>
                            <th>{{ __('ui.acceptance_plan_check') }}</th>
                            <th>{{ __('ui.type') }}</th>
                            <th>{{ __('ui.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="font-medium whitespace-nowrap">
                                    {{ $row['test_id'] }}
                                    @if (($row['source'] ?? '') === 'fr')
                                        <span class="kt-badge kt-badge-sm kt-badge-outline ms-1">{{ __('ui.acceptance_plan_source_fr') }}</span>
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
                                    @elseif (! empty($row['functional_requirement_id']))
                                        <a href="{{ model_route('FunctionalRequirement', 'show', $row['functional_requirement_id']) }}"
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
                                    @if (($row['rule'] ?? '') !== '')
                                        {{ $row['rule'] }}
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if (! empty($row['scenario_id']))
                                        <a href="{{ model_route('Scenario', 'show', $row['scenario_id']) }}"
                                           class="text-primary hover:underline">
                                            {{ $row['scenario_title'] }}
                                        </a>
                                    @elseif (($row['scenario_title'] ?? '') !== '')
                                        {{ $row['scenario_title'] }}
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    @if (($row['type'] ?? '') === 'Edge Case')
                                        <span class="kt-badge kt-badge-sm kt-badge-warning">{{ __('ui.edge_case') }}</span>
                                    @else
                                        <span class="kt-badge kt-badge-sm kt-badge-outline">{{ __('ui.happy_path') }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">{{ $row['status'] ?? __('ui.draft') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-secondary-foreground">{{ __('ui.acceptance_plan_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-card>
@endsection
