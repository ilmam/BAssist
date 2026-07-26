@extends('layouts.print')

@section('title', ($project->name ?? __('ui.project')).' — '.__('ui.project_export_pack'))

@section('toolbar')
    <div class="print-toolbar no-print">
        <p class="print-toolbar__hint">{{ __('ui.project_export_print_hint') }}</p>
        <div class="print-toolbar__actions">
            <a class="print-btn" href="{{ model_route('Project', 'index') }}">{{ __('ui.back_to_projects') }}</a>
            <button type="button" class="print-btn print-btn--primary" data-print-pack>
                {{ __('ui.print_to_pdf') }}
            </button>
        </div>
    </div>
@endsection

@section('content')
    @php
        $hasObjectives = $objectives->isNotEmpty();
        $hasNeeds = $needs->isNotEmpty();
        $hasStakeholders = $stakeholders->isNotEmpty();
        $hasStakeholderNeeds = $stakeholder_needs->isNotEmpty();
        $hasStateFlows = $state_flows !== [];
        $hasSwimlaneFlows = $swimlane_flows !== [];
        $matrixRows = $matrix['rows'] ?? [];
        $hasMatrix = $matrixRows !== [];
        $hasAnyArtifacts = $hasObjectives
            || $hasNeeds
            || $hasStakeholders
            || $hasStakeholderNeeds
            || $hasStateFlows
            || $hasSwimlaneFlows
            || $hasMatrix;
    @endphp

    <header class="cover">
        <p class="cover__eyebrow">{{ __('ui.project_export_pack') }}</p>
        <h1>{{ $project->name }}</h1>
        @if ($project->description)
            <p class="cover__description">{{ $project->description }}</p>
        @endif
        <div class="cover__meta">
            <div>
                <strong>{{ __('ui.code') }}</strong>
                {{ $project->code ?: '—' }}
            </div>
            <div>
                <strong>{{ __('ui.workspace') }}</strong>
                {{ $project->workspace?->name ?: '—' }}
            </div>
            <div>
                <strong>{{ __('ui.status') }}</strong>
                {{ $project->status?->name ?: '—' }}
            </div>
            <div>
                <strong>{{ __('ui.generated_at') }}</strong>
                {{ $generated_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
            </div>
        </div>
        @unless ($hasAnyArtifacts)
            <p class="empty" style="margin-top: 1.25rem;">{{ __('ui.export_no_artifacts') }}</p>
        @endunless
    </header>

    @if ($hasObjectives)
        <h2 class="section-title">{{ __('ui.business_objectives') }}</h2>
        @foreach ($objectives as $objective)
            <article class="artifact">
                <div class="artifact__code">{{ $objective->number ?: '#'.$objective->id }}</div>
                <h3 class="item-title">{{ $objective->title }}</h3>
                <dl class="kv">
                    <dt>{{ __('ui.status') }}</dt>
                    <dd>{{ $objective->status?->name ?: '—' }}</dd>
                    <dt>{{ __('ui.priority') }}</dt>
                    <dd>{{ $objective->priority?->name ?: '—' }}</dd>
                    @if ($objective->success_measure)
                        <dt>{{ __('ui.success_measure') }}</dt>
                        <dd>{{ $objective->success_measure }}</dd>
                    @endif
                    @if ($objective->potential_value)
                        <dt>{{ __('ui.potential_value') }}</dt>
                        <dd>{{ $objective->potential_value }}</dd>
                    @endif
                </dl>
                @if ($objective->description)
                    <p class="prose">{{ $objective->description }}</p>
                @endif
            </article>
        @endforeach
    @endif

    @if ($hasNeeds)
        <h2 class="section-title">{{ __('ui.business_needs') }}</h2>
        @foreach ($needs as $need)
            <article class="artifact">
                <div class="artifact__code">{{ $need->number ?: '#'.$need->id }}</div>
                <h3 class="item-title">{{ $need->title }}</h3>
                <dl class="kv">
                    <dt>{{ __('ui.status') }}</dt>
                    <dd>{{ $need->status?->name ?: '—' }}</dd>
                    <dt>{{ __('ui.priority') }}</dt>
                    <dd>{{ $need->priority?->name ?: '—' }}</dd>
                    @if ($need->need_type)
                        <dt>{{ __('ui.need_type') }}</dt>
                        <dd>{{ $need->need_type }}</dd>
                    @endif
                    <dt>{{ __('ui.business_objectives') }}</dt>
                    <dd>
                        @php
                            $linkedObjectives = $need->businessObjectives->pluck('title')->filter()->values();
                        @endphp
                        {{ $linkedObjectives->isNotEmpty() ? $linkedObjectives->implode('; ') : '—' }}
                    </dd>
                </dl>
                @if ($need->description)
                    <p class="prose">{{ $need->description }}</p>
                @endif
                @if ($need->rationale)
                    <dl class="kv">
                        <dt>{{ __('ui.rationale') }}</dt>
                        <dd>{{ $need->rationale }}</dd>
                    </dl>
                @endif
                @if ($need->impact)
                    <dl class="kv">
                        <dt>{{ __('ui.impact') }}</dt>
                        <dd>{{ $need->impact }}</dd>
                    </dl>
                @endif
                @if ($need->do_nothing_consequence)
                    <dl class="kv">
                        <dt>{{ __('ui.do_nothing_consequence') }}</dt>
                        <dd>{{ $need->do_nothing_consequence }}</dd>
                    </dl>
                @endif
            </article>
        @endforeach
    @endif

    @if ($hasStakeholders)
        <h2 class="section-title">{{ __('ui.stakeholders') }}</h2>
        @foreach ($stakeholders as $stakeholder)
            <article class="artifact">
                <h3 class="item-title">{{ $stakeholder->name }}</h3>
                <dl class="kv">
                    <dt>{{ __('ui.status') }}</dt>
                    <dd>{{ $stakeholder->status?->name ?: '—' }}</dd>
                    @if ($stakeholder->type)
                        <dt>{{ __('ui.type') }}</dt>
                        <dd>{{ $stakeholder->type }}</dd>
                    @endif
                    @if ($stakeholder->influence)
                        <dt>{{ __('ui.influence') }}</dt>
                        <dd>{{ $stakeholder->influence }}</dd>
                    @endif
                    @if ($stakeholder->interest)
                        <dt>{{ __('ui.interest') }}</dt>
                        <dd>{{ $stakeholder->interest }}</dd>
                    @endif
                    @if ($stakeholder->is_system)
                        <dt>{{ __('ui.is_system') }}</dt>
                        <dd>{{ __('ui.yes') }}</dd>
                    @endif
                </dl>
                @if ($stakeholder->notes)
                    <p class="prose">{{ $stakeholder->notes }}</p>
                @endif
            </article>
        @endforeach
    @endif

    @if ($hasStakeholderNeeds)
        <h2 class="section-title">{{ __('ui.stakeholder_needs') }}</h2>
        @foreach ($stakeholder_needs as $sn)
            <article class="artifact">
                <div class="artifact__code">{{ $sn->number ?: '#'.$sn->id }}</div>
                <h3 class="item-title">{{ $sn->title }}</h3>
                <dl class="kv">
                    <dt>{{ __('ui.status') }}</dt>
                    <dd>{{ $sn->status?->name ?: '—' }}</dd>
                    <dt>{{ __('ui.priority') }}</dt>
                    <dd>{{ $sn->priority?->name ?: '—' }}</dd>
                    <dt>{{ __('ui.stakeholders') }}</dt>
                    <dd>
                        @php
                            $linkedStakeholders = $sn->stakeholders->pluck('name')->filter()->values();
                        @endphp
                        {{ $linkedStakeholders->isNotEmpty() ? $linkedStakeholders->implode('; ') : '—' }}
                    </dd>
                    <dt>{{ __('ui.business_needs') }}</dt>
                    <dd>
                        @php
                            $linkedNeeds = $sn->businessNeeds->pluck('title')->filter()->values();
                        @endphp
                        {{ $linkedNeeds->isNotEmpty() ? $linkedNeeds->implode('; ') : '—' }}
                    </dd>
                </dl>
                @if ($sn->description)
                    <p class="prose">{{ $sn->description }}</p>
                @endif
            </article>
        @endforeach
    @endif

    @if ($hasStateFlows)
        <h2 class="section-title">{{ __('ui.state_flows') }}</h2>
        @foreach ($state_flows as $item)
            @php $flow = $item['model']; @endphp
            <article class="artifact">
                <h3 class="item-title">{{ $flow->title }}</h3>
                <dl class="kv">
                    <dt>{{ __('ui.status') }}</dt>
                    <dd>{{ $flow->status?->name ?: '—' }}</dd>
                </dl>
                @if ($flow->description)
                    <p class="prose">{{ $flow->description }}</p>
                @endif
                @php
                    $mermaidBody = trim($item['mermaid']);
                    $hasDiagram = $mermaidBody !== '' && substr_count($mermaidBody, "\n") > 0;
                @endphp
                @if ($hasDiagram)
                    <div class="diagram">
                        <pre class="mermaid" data-mermaid>{{ $item['mermaid'] }}</pre>
                    </div>
                @else
                    <p class="empty">{{ __('ui.no_transitions') }}</p>
                @endif
            </article>
        @endforeach
    @endif

    @if ($hasSwimlaneFlows)
        <h2 class="section-title">{{ __('ui.swimlane_flows') }}</h2>
        @foreach ($swimlane_flows as $item)
            @php $flow = $item['model']; @endphp
            <article class="artifact">
                <h3 class="item-title">{{ $flow->title }}</h3>
                <dl class="kv">
                    <dt>{{ __('ui.status') }}</dt>
                    <dd>{{ $flow->status?->name ?: '—' }}</dd>
                    <dt>{{ __('ui.direction') }}</dt>
                    <dd>{{ ($flow->direction ?? 'TB') === 'LR' ? __('ui.direction_lr') : __('ui.direction_tb') }}</dd>
                </dl>
                @if ($flow->description)
                    <p class="prose">{{ $flow->description }}</p>
                @endif
                @php
                    $mermaidBody = trim($item['mermaid']);
                    $hasDiagram = $mermaidBody !== '' && substr_count($mermaidBody, "\n") > 0;
                @endphp
                @if ($hasDiagram)
                    <div class="diagram">
                        <pre class="mermaid" data-mermaid>{{ $item['mermaid'] }}</pre>
                    </div>
                @else
                    <p class="empty">{{ __('ui.no_elements') }}</p>
                @endif
            </article>
        @endforeach
    @endif

    @if ($hasMatrix)
        <h2 class="section-title">{{ __('ui.traceability_matrix') }}</h2>
        <div class="summary">
            <span>{{ __('ui.matrix_total') }}: <strong>{{ $matrix['summary']['total'] ?? 0 }}</strong></span>
            <span>{{ __('ui.matrix_gaps') }}: <strong>{{ $matrix['summary']['gaps'] ?? 0 }}</strong></span>
        </div>
        <table class="matrix">
            <thead>
                <tr>
                    <th>{{ __('ui.business_objective') }}</th>
                    <th>{{ __('ui.business_need') }}</th>
                    <th>{{ __('ui.stakeholder_need') }}</th>
                    <th>{{ __('ui.feature') }}</th>
                    <th>{{ __('ui.stakeholders') }}</th>
                    <th>{{ __('ui.gaps') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($matrixRows as $row)
                    <tr @class(['has-gap' => ! empty($row['has_gap'])])>
                        <td>
                            @if (! empty($row['objective_number']) || ! empty($row['objective_title']))
                                <div class="artifact__code">{{ $row['objective_number'] ?? '' }}</div>
                                {{ $row['objective_title'] ?? '' }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if (! empty($row['need_number']) || ! empty($row['need_title']))
                                <div class="artifact__code">{{ $row['need_number'] ?? '' }}</div>
                                {{ $row['need_title'] ?? '' }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if (! empty($row['stakeholder_need_number']) || ! empty($row['stakeholder_need_title']))
                                <div class="artifact__code">{{ $row['stakeholder_need_number'] ?? '' }}</div>
                                {{ $row['stakeholder_need_title'] ?? '' }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if (! empty($row['feature_code']) || ! empty($row['feature_title']))
                                <div class="artifact__code">{{ $row['feature_code'] ?? '' }}</div>
                                {{ $row['feature_title'] ?? '' }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ ! empty($row['stakeholder_names']) ? implode('; ', $row['stakeholder_names']) : '—' }}</td>
                        <td>
                            @php
                                $gapLabels = collect($row['gaps'] ?? [])->map(function ($gap) {
                                    return match ($gap) {
                                        'missing_objective' => __('ui.gap_missing_objective'),
                                        'missing_need' => __('ui.gap_missing_need'),
                                        'missing_stakeholder_need' => __('ui.gap_missing_stakeholder_need'),
                                        'orphan_objective' => __('ui.gap_orphan_objective'),
                                        'orphan_stakeholder_need' => __('ui.gap_orphan_stakeholder_need'),
                                        'orphan_feature' => __('ui.gap_orphan_feature'),
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
@endsection

@push('scripts')
    @vite(['resources/js/project-export-print.js'])
@endpush
