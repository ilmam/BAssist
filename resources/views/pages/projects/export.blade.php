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
        $hasAssumptions = $assumptions->isNotEmpty();
        $hasConstraints = $constraints->isNotEmpty();
        $hasBusinessRules = $business_rules->isNotEmpty();
        $matrixRows = $matrix['rows'] ?? [];
        $hasMatrix = $matrixRows !== [];
        $readinessItems = $readiness['items'] ?? [];
        $readinessTotal = (int) ($readiness['total_gaps'] ?? 0);
        $hasAnyArtifacts = $hasObjectives
            || $hasNeeds
            || $hasStakeholders
            || $hasStakeholderNeeds
            || $hasStateFlows
            || $hasSwimlaneFlows
            || $hasAssumptions
            || $hasConstraints
            || $hasBusinessRules
            || $hasMatrix
            || $readinessItems !== [];
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

    <h2 class="section-title">{{ __('ui.project_readiness') }}</h2>
    <div class="summary">
        <span>{{ __('ui.readiness_gap_count', ['count' => $readinessTotal]) }}</span>
    </div>
    <p class="muted" style="margin: 0 0 0.75rem; font: 13px/1.45 system-ui, sans-serif;">
        {{ __('ui.project_readiness_help') }}
    </p>
    @if ($readinessItems === [])
        <p class="empty">{{ __('ui.readiness_all_clear') }}</p>
    @else
        <table class="matrix">
            <thead>
                <tr>
                    <th>{{ __('ui.readiness_gap') }}</th>
                    <th style="width: 6rem;">{{ __('ui.count') }}</th>
                    <th style="width: 7rem;">{{ __('ui.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($readinessItems as $gap)
                    <tr>
                        <td>{{ $gap['label'] }}</td>
                        <td>{{ $gap['count'] }}</td>
                        <td>
                            @if (($gap['severity'] ?? '') === 'critical')
                                {{ __('ui.readiness_severity_critical') }}
                            @elseif (($gap['severity'] ?? '') === 'warn')
                                {{ __('ui.readiness_severity_warn') }}
                            @else
                                {{ __('ui.readiness_severity_info') }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($hasObjectives)
        <h2 class="section-title">{{ __('ui.business_objectives') }}</h2>
        @foreach ($objectives as $objective)
            <article class="artifact">
                <h3 class="item-title">
                    @if ($objective->code)
                        <span class="artifact__code">{{ $objective->code }}</span>
                    @endif
                    {{ $objective->title }}
                </h3>
                <div class="artifact__panel">
                    <div class="artifact__meta">
                        <span><strong>{{ __('ui.status') }}</strong>{{ $objective->status?->name ?: '—' }}</span>
                        <span><strong>{{ __('ui.priority') }}</strong>{{ $objective->priority?->name ?: '—' }}</span>
                    </div>
                    @if ($objective->success_measure || $objective->potential_value)
                        <dl class="kv">
                            @if ($objective->success_measure)
                                <dt>{{ __('ui.success_measure') }}</dt>
                                <dd>{{ $objective->success_measure }}</dd>
                            @endif
                            @if ($objective->potential_value)
                                <dt>{{ __('ui.potential_value') }}</dt>
                                <dd>{{ $objective->potential_value }}</dd>
                            @endif
                        </dl>
                    @endif
                </div>
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
                <h3 class="item-title">
                    @if ($need->code)
                        <span class="artifact__code">{{ $need->code }}</span>
                    @endif
                    {{ $need->title }}
                </h3>
                <div class="artifact__panel">
                    <div class="artifact__meta">
                        <span><strong>{{ __('ui.status') }}</strong>{{ $need->status?->name ?: '—' }}</span>
                        <span><strong>{{ __('ui.priority') }}</strong>{{ $need->priority?->name ?: '—' }}</span>
                    </div>
                    <dl class="kv">
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
                </div>
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
        <table class="matrix">
            <thead>
                <tr>
                    <th>{{ __('ui.stakeholder') }}</th>
                    <th>{{ __('ui.type') }}</th>
                    <th>{{ __('ui.responsibility') }}</th>
                    <th>{{ __('ui.influence') }}</th>
                    <th>{{ __('ui.interest') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stakeholders as $stakeholder)
                    <tr>
                        <td>{{ $stakeholder->name ?: '—' }}</td>
                        <td>{{ $stakeholder->type ?: '—' }}</td>
                        <td>{{ $stakeholder->notes ?: '—' }}</td>
                        <td>{{ $stakeholder->influence ?: '—' }}</td>
                        <td>{{ $stakeholder->interest ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($hasStakeholderNeeds)
        <h2 class="section-title">{{ __('ui.stakeholder_needs') }}</h2>
        @foreach ($stakeholder_needs as $sn)
            <article class="artifact">
                <h3 class="item-title">
                    @if ($sn->code)
                        <span class="artifact__code">{{ $sn->code }}</span>
                    @endif
                    {{ $sn->title }}
                </h3>
                <div class="artifact__panel">
                    <div class="artifact__meta">
                        <span><strong>{{ __('ui.status') }}</strong>{{ $sn->status?->name ?: '—' }}</span>
                        <span><strong>{{ __('ui.priority') }}</strong>{{ $sn->priority?->name ?: '—' }}</span>
                    </div>
                    <dl class="kv">
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
                </div>
                @if ($sn->description)
                    <p class="prose">{{ $sn->description }}</p>
                @endif
            </article>
        @endforeach
    @endif

    @if ($hasStateFlows)
        <h2 class="section-title">{{ __('ui.state_flows') }}</h2>
        @foreach ($state_flows as $item)
            @php
                $flow = $item['model'];
                $mermaidBody = trim($item['mermaid'] ?? '');
                $hasDiagram = $mermaidBody !== '';
            @endphp
            <article class="artifact">
                <h3 class="item-title">{{ $flow->title }}</h3>
                <div class="artifact__panel">
                    <div class="artifact__meta">
                        <span><strong>{{ __('ui.status') }}</strong>{{ $flow->status?->name ?: '—' }}</span>
                    </div>
                </div>
                @if ($flow->description)
                    <p class="prose">{{ $flow->description }}</p>
                @endif
                @if ($hasDiagram)
                    <div
                        class="diagram"
                        data-export-diagram
                        data-mermaid="{{ base64_encode($mermaidBody) }}"
                    ></div>
                @else
                    <p class="empty">{{ __('ui.no_transitions') }}</p>
                @endif
            </article>
        @endforeach
    @endif

    @if ($hasSwimlaneFlows)
        <h2 class="section-title">{{ __('ui.swimlane_flows') }}</h2>
        @foreach ($swimlane_flows as $item)
            @php
                $flow = $item['model'];
                $mermaidBody = trim($item['mermaid'] ?? '');
                $hasDiagram = $mermaidBody !== '';
            @endphp
            <article class="artifact">
                <h3 class="item-title">{{ $flow->title }}</h3>
                <div class="artifact__panel">
                    <div class="artifact__meta">
                        <span><strong>{{ __('ui.status') }}</strong>{{ $flow->status?->name ?: '—' }}</span>
                    </div>
                </div>
                @if ($flow->description)
                    <p class="prose">{{ $flow->description }}</p>
                @endif
                @if ($hasDiagram)
                    <div
                        class="diagram"
                        data-export-diagram
                        data-mermaid="{{ base64_encode($mermaidBody) }}"
                    ></div>
                @else
                    <p class="empty">{{ __('ui.no_elements') }}</p>
                @endif
            </article>
        @endforeach
    @endif

    @if ($hasAssumptions)
        <h2 class="section-title">{{ __('ui.assumptions') }}</h2>
        @foreach ($assumptions as $item)
            <article class="artifact">
                <h3 class="item-title">{{ $item->title }}</h3>
                <div class="artifact__meta">
                    <span><strong>{{ __('ui.status') }}</strong>{{ $item->statusLabel() }}</span>
                </div>
                @if ($item->source)
                    <dl class="kv">
                        <dt>{{ __('ui.source') }}</dt>
                        <dd>{{ $item->source }}</dd>
                    </dl>
                @endif
                @if ($item->description)
                    <p class="prose">{{ $item->description }}</p>
                @endif
            </article>
        @endforeach
    @endif

    @if ($hasConstraints)
        <h2 class="section-title">{{ __('ui.constraints') }}</h2>
        @foreach ($constraints as $item)
            <article class="artifact">
                <h3 class="item-title">{{ $item->title }}</h3>
                <div class="artifact__meta">
                    <span><strong>{{ __('ui.status') }}</strong>{{ $item->statusLabel() }}</span>
                </div>
                @if ($item->source)
                    <dl class="kv">
                        <dt>{{ __('ui.source') }}</dt>
                        <dd>{{ $item->source }}</dd>
                    </dl>
                @endif
                @if ($item->description)
                    <p class="prose">{{ $item->description }}</p>
                @endif
            </article>
        @endforeach
    @endif

    @if ($hasBusinessRules)
        <h2 class="section-title">{{ __('ui.business_rules') }}</h2>
        @foreach ($business_rules as $item)
            <article class="artifact">
                <h3 class="item-title">{{ $item->title }}</h3>
                <div class="artifact__meta">
                    <span><strong>{{ __('ui.status') }}</strong>{{ $item->statusLabel() }}</span>
                </div>
                @if ($item->source)
                    <dl class="kv">
                        <dt>{{ __('ui.source') }}</dt>
                        <dd>{{ $item->source }}</dd>
                    </dl>
                @endif
                @if ($item->description)
                    <p class="prose">{{ $item->description }}</p>
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
                            @if (! empty($row['feature_code']) || ! empty($row['feature_title']))
                                @if (! empty($row['feature_code']))
                                    <span class="artifact__code">{{ $row['feature_code'] }}</span>
                                @endif
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
