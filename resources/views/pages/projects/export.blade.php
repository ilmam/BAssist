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
        $architecture = $architecture ?? null;
        $hasArchitecture = is_array($architecture) && ($architecture['views'] ?? []) !== [];
        $hasAssumptions = $assumptions->isNotEmpty();
        $risks = $risks ?? collect();
        $hasRisks = $risks->isNotEmpty();
        $hasConstraints = $constraints->isNotEmpty();
        $hasBusinessRules = $business_rules->isNotEmpty();
        $hasStrategicBaseline = $strategic_baseline !== null;
        $hasScopeItems = $scope_items->isNotEmpty();
        $hasFunctionalRequirements = $functional_requirements->isNotEmpty();
        $hasFeatures = $features !== [];
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
            || $hasArchitecture
            || $hasAssumptions
            || $hasRisks
            || $hasConstraints
            || $hasBusinessRules
            || $hasStrategicBaseline
            || $hasScopeItems
            || $hasFunctionalRequirements
            || $hasFeatures
            || $hasMatrix
            || $readinessItems !== [];

        $tocSections = array_values(array_filter([
            $hasStrategicBaseline ? ['id' => 'section-strategic-baseline', 'label' => __('ui.strategic_baseline')] : null,
            $hasScopeItems ? ['id' => 'section-scope-items', 'label' => __('ui.scope_items')] : null,
            $hasObjectives ? ['id' => 'section-business-objectives', 'label' => __('ui.business_objectives')] : null,
            $hasNeeds ? ['id' => 'section-business-needs', 'label' => __('ui.business_needs')] : null,
            $hasStakeholders ? ['id' => 'section-stakeholders', 'label' => __('ui.stakeholders')] : null,
            $hasStakeholderNeeds ? ['id' => 'section-stakeholder-needs', 'label' => __('ui.stakeholder_needs')] : null,
            $hasArchitecture ? ['id' => 'section-architecture', 'label' => __('ui.architecture_c4')] : null,
            $hasStateFlows ? ['id' => 'section-state-flows', 'label' => __('ui.state_flows')] : null,
            $hasSwimlaneFlows ? ['id' => 'section-swimlane-flows', 'label' => __('ui.swimlane_flows')] : null,
            $hasAssumptions ? ['id' => 'section-assumptions', 'label' => __('ui.assumptions')] : null,
            $hasRisks ? ['id' => 'section-risks', 'label' => __('ui.risk_assessment')] : null,
            $hasConstraints ? ['id' => 'section-constraints', 'label' => __('ui.constraints')] : null,
            $hasBusinessRules ? ['id' => 'section-business-rules', 'label' => __('ui.business_rules')] : null,
            $hasFunctionalRequirements ? ['id' => 'section-functional-requirements', 'label' => __('ui.functional_requirements')] : null,
            $hasFeatures ? ['id' => 'section-features', 'label' => __('ui.features')] : null,
            $hasMatrix ? ['id' => 'section-traceability-matrix', 'label' => __('ui.traceability_matrix')] : null,
        ]));
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

    @if ($tocSections !== [])
        <nav class="export-toc" aria-label="{{ __('ui.table_of_contents') }}">
            <h2 class="section-title">{{ __('ui.table_of_contents') }}</h2>
            <ol class="export-toc__list">
                @foreach ($tocSections as $entry)
                    <li>
                        <a href="#{{ $entry['id'] }}">{{ $entry['label'] }}</a>
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

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

    @if ($hasStrategicBaseline)
        <h2 id="section-strategic-baseline" class="section-title section-title--break">{{ __('ui.strategic_baseline') }}</h2>
        <article class="artifact strategic-baseline">
            <h3 class="item-title">{{ $strategic_baseline->project?->name ?? __('ui.strategic_baseline') }}</h3>
            <div class="artifact__meta">
                <span><strong>{{ __('ui.status') }}</strong>{{ $strategic_baseline->statusLabel() }}</span>
            </div>
            <dl class="kv">
                @if ($strategic_baseline->current_state)
                    <dt>{{ __('ui.current_state') }}</dt>
                    <dd class="prose">{{ $strategic_baseline->current_state }}</dd>
                @endif
                @if ($strategic_baseline->future_state)
                    <dt>{{ __('ui.future_state') }}</dt>
                    <dd class="prose">{{ $strategic_baseline->future_state }}</dd>
                @endif
                @if ($strategic_baseline->change_strategy)
                    <dt>{{ __('ui.change_strategy') }}</dt>
                    <dd class="prose">{{ $strategic_baseline->change_strategy }}</dd>
                @endif
            </dl>
        </article>
    @endif

    @if ($hasScopeItems)
        <h2 id="section-scope-items" class="section-title">{{ __('ui.scope_items') }}</h2>
        @php
            $inScopeItems = $scope_items->where('direction', \App\Support\ScopeItemDirection::IN)->values();
            $outScopeItems = $scope_items->where('direction', \App\Support\ScopeItemDirection::OUT)->values();
        @endphp
        <table class="matrix scope-columns">
            <thead>
                <tr>
                    <th>{{ __('ui.scope_item_direction_in') }}</th>
                    <th>{{ __('ui.scope_item_direction_out') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        @foreach ($inScopeItems as $item)
                            <article class="artifact">
                                <h3 class="item-title">{{ $item->title }}</h3>
                                @if ($item->description)
                                    <p class="prose">{{ $item->description }}</p>
                                @endif
                            </article>
                        @endforeach
                    </td>
                    <td>
                        @foreach ($outScopeItems as $item)
                            <article class="artifact">
                                <h3 class="item-title">{{ $item->title }}</h3>
                                @if ($item->description)
                                    <p class="prose">{{ $item->description }}</p>
                                @endif
                            </article>
                        @endforeach
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

    @if ($hasObjectives)
        <h2 id="section-business-objectives" class="section-title">{{ __('ui.business_objectives') }}</h2>
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
        <h2 id="section-business-needs" class="section-title">{{ __('ui.business_needs') }}</h2>
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
        <h2 id="section-stakeholders" class="section-title">{{ __('ui.stakeholders') }}</h2>
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
        <h2 id="section-stakeholder-needs" class="section-title">{{ __('ui.stakeholder_needs') }}</h2>
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

    @if ($hasArchitecture)
        @include('pages.projects.babok.partials.architecture-c4', [
            'architecture' => $architecture,
            'sectionId' => 'section-architecture',
            'sectionBreak' => true,
        ])
    @endif

    @if ($hasStateFlows)
        <h2 id="section-state-flows" class="section-title">{{ __('ui.state_flows') }}</h2>
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
        <h2 id="section-swimlane-flows" class="section-title">{{ __('ui.swimlane_flows') }}</h2>
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
        <h2 id="section-assumptions" class="section-title">{{ __('ui.assumptions') }}</h2>
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

    @if ($hasRisks)
        <h2 id="section-risks" class="section-title">{{ __('ui.risk_assessment') }}</h2>
        @include('pages.projects.babok.partials.risk-assessment', ['risks' => $risks])
    @endif

    @if ($hasConstraints)
        <h2 id="section-constraints" class="section-title">{{ __('ui.constraints') }}</h2>
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
        <h2 id="section-business-rules" class="section-title">{{ __('ui.business_rules') }}</h2>
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

    @if ($hasFunctionalRequirements)
        <h2 id="section-functional-requirements" class="section-title section-title--break">{{ __('ui.functional_requirements') }}</h2>
        @foreach ($functional_requirements as $requirement)
            <article class="artifact">
                <h3 class="item-title">
                    @if ($requirement->code)
                        <span class="artifact__code">{{ $requirement->code }}</span>
                    @endif
                    {{ $requirement->title }}
                </h3>
                <div class="artifact__panel">
                    <div class="artifact__meta">
                        <span><strong>{{ __('ui.status') }}</strong>{{ $requirement->status?->name ?: '—' }}</span>
                        <span><strong>{{ __('ui.priority') }}</strong>{{ $requirement->priority?->name ?: '—' }}</span>
                    </div>
                    <dl class="kv">
                        <dt>{{ __('ui.stakeholder_need') }}</dt>
                        <dd>
                            @if ($requirement->stakeholderNeed)
                                @if ($requirement->stakeholderNeed->code)
                                    <span class="artifact__code">{{ $requirement->stakeholderNeed->code }}</span>
                                @endif
                                {{ $requirement->stakeholderNeed->title }}
                            @else
                                —
                            @endif
                        </dd>
                        <dt>{{ __('ui.statement') }}</dt>
                        <dd>{{ $requirement->statement ?: '—' }}</dd>
                        @if (filled($requirement->trigger))
                            <dt>{{ __('ui.trigger') }}</dt>
                            <dd>{{ $requirement->trigger }}</dd>
                        @endif
                        @if (filled($requirement->acceptance_criteria))
                            <dt>{{ __('ui.acceptance_criteria') }}</dt>
                            <dd>{{ $requirement->acceptance_criteria }}</dd>
                        @endif
                    </dl>
                </div>
            </article>
        @endforeach
    @endif

    @if ($hasFeatures)
        <h2 id="section-features" class="section-title section-title--break">{{ __('ui.features') }}</h2>
        @foreach ($features as $item)
            @php
                $feature = $item['model'];
                $gherkinBody = trim($item['gherkin'] ?? '');
            @endphp
            <article class="artifact">
                <h3 class="item-title">
                    @if ($feature->code)
                        <span class="artifact__code">{{ $feature->code }}</span>
                    @endif
                    {{ $feature->title }}
                </h3>
                <div class="artifact__panel">
                    <div class="artifact__meta">
                        <span><strong>{{ __('ui.status') }}</strong>{{ $feature->status?->name ?: '—' }}</span>
                        <span><strong>{{ __('ui.priority') }}</strong>{{ $feature->priority?->name ?: '—' }}</span>
                    </div>
                    <dl class="kv">
                        <dt>{{ __('ui.stakeholder_need') }}</dt>
                        <dd>
                            @if ($feature->stakeholderNeed)
                                @if ($feature->stakeholderNeed->code)
                                    <span class="artifact__code">{{ $feature->stakeholderNeed->code }}</span>
                                @endif
                                {{ $feature->stakeholderNeed->title }}
                            @else
                                —
                            @endif
                        </dd>
                    </dl>
                </div>
                @if ($gherkinBody !== '')
                    <pre class="gherkin-print">{{ $gherkinBody }}</pre>
                @endif
            </article>
        @endforeach
    @endif

    @if ($hasMatrix)
        <h2 id="section-traceability-matrix" class="section-title section-title--break">{{ __('ui.traceability_matrix') }}</h2>
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
                    <th>{{ __('ui.solution_requirement') }}</th>
                    <th>{{ __('ui.process_step') }}</th>
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
                            @elseif (! empty($row['functional_requirement_code']) || ! empty($row['functional_requirement_title']))
                                @if (! empty($row['functional_requirement_code']))
                                    <span class="artifact__code">{{ $row['functional_requirement_code'] }}</span>
                                @endif
                                {{ $row['functional_requirement_title'] ?? '' }}
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
                        <td>{{ ! empty($row['stakeholder_names']) ? implode('; ', $row['stakeholder_names']) : '—' }}</td>
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
@endsection

@push('styles')
    <style>
        .export-toc {
            margin: 0 0 0.5rem;
        }

        .export-toc__list {
            margin: 0;
            padding: 0 0 0 1.25rem;
            font: 14px/1.55 system-ui, sans-serif;
            color: var(--ink);
        }

        .export-toc__list li {
            margin: 0.2rem 0;
        }

        .export-toc__list a {
            color: var(--ink);
            text-decoration: underline;
            text-underline-offset: 0.15em;
        }

        .export-toc__list a:hover {
            color: #000;
        }

        .gherkin-print {
            margin: 0.75rem 0 0;
            padding: 1rem 1.1rem;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            font: 12px/1.55 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            white-space: pre-wrap;
            word-break: break-word;
            color: #111;
            page-break-inside: auto;
        }

        /* Narrative blocks: looser than dense .kv / matrix rows, still print-friendly */
        .strategic-baseline .artifact__meta {
            margin-bottom: 1.1rem;
        }

        .strategic-baseline .kv {
            display: flex;
            flex-direction: column;
            gap: 0;
            margin: 0;
            grid-template-columns: unset;
        }

        .strategic-baseline .kv dt {
            margin: 1.35rem 0 0;
            padding-top: 1.1rem;
            border-top: 1px solid #ececec;
            font-weight: 600;
            color: var(--muted);
        }

        .strategic-baseline .kv dt:first-child {
            margin-top: 0;
            padding-top: 0;
            border-top: 0;
        }

        .strategic-baseline .kv dd {
            margin: 0.45rem 0 0;
        }

        .strategic-baseline .kv dd.prose {
            margin-top: 0.45rem;
            line-height: 1.55;
        }

        table.scope-columns th,
        table.scope-columns td {
            width: 50%;
        }

        table.scope-columns .artifact {
            margin-bottom: 0.85rem;
            padding-bottom: 0.75rem;
        }

        table.scope-columns .artifact:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: 0;
        }

        table.scope-columns h3.item-title {
            margin-top: 0;
            font-size: 0.95rem;
        }

        .doc-note {
            margin: 0 0 1.25rem;
            padding: 0.75rem 1rem;
            background: var(--surface);
            border-left: 3px solid var(--accent);
            font: 13px/1.45 system-ui, sans-serif;
            color: var(--muted);
        }

        .risk-score-chip {
            display: inline-block;
            margin-left: 0.5rem;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            font: 600 11px/1.3 system-ui, sans-serif;
            vertical-align: middle;
            background: #eef2f6;
            color: #4b5675;
        }

        .risk-score-chip--high {
            background: #fff3e0;
            color: #b54708;
        }

        .risk-score-chip--critical {
            background: #ffe4e2;
            color: #b42318;
        }

        .artifact--risk-critical {
            border-left: 3px solid #f04438;
            padding-left: 0.75rem;
            background: #fffbfa;
        }

        .artifact--coverage-gap {
            outline: 1px dashed #f04438;
            outline-offset: 2px;
        }

        .risk-gap-flag {
            margin: 0.75rem 0 0;
            font: 600 12px/1.4 system-ui, sans-serif;
            color: #b42318;
        }

        @media print {
            .export-toc__list {
                font-size: 11pt;
            }

            .export-toc__list a {
                text-decoration: none;
            }

            .gherkin-print {
                border: 0;
                border-radius: 0;
                padding: 0;
                font-size: 10.5pt;
                break-inside: auto;
                page-break-inside: auto;
            }

            /* Heavy sections: start on a fresh page */
            h2.section-title.section-title--break {
                break-before: page;
                page-break-before: always;
            }

            /* Long baseline: allow split between narrative fields, keep each field together */
            .artifact.strategic-baseline {
                break-inside: auto;
                page-break-inside: auto;
            }

            .strategic-baseline .kv {
                break-inside: auto;
                page-break-inside: auto;
            }

            .strategic-baseline .kv dt {
                margin-top: 1.1rem;
                padding-top: 0.9rem;
                break-after: avoid;
                page-break-after: avoid;
            }

            .strategic-baseline .kv dt:first-child {
                margin-top: 0;
                padding-top: 0;
            }

            .strategic-baseline .kv dd {
                break-before: avoid;
                page-break-before: avoid;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            /* Scope table may span pages; keep each item card intact */
            table.scope-columns {
                break-inside: auto;
                page-break-inside: auto;
            }

            table.scope-columns th,
            table.scope-columns td {
                width: 50%;
            }

            table.scope-columns .artifact {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            /* Keep matrix heading + summary with the table header row */
            h2.section-title.section-title--break + .summary {
                break-after: avoid;
                page-break-after: avoid;
            }

            .artifact--risk-critical,
            .risk-score-chip--critical {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
@endpush

@push('scripts')
    @vite(['resources/js/project-export-print.js'])
@endpush
