@php
    $readinessItems = $readiness['items'] ?? [];
    $readinessTotal = (int) ($readiness['total_gaps'] ?? 0);
@endphp

<h2 class="section-title">{{ __('ui.project_readiness') }}</h2>
<div class="summary">
    <span>{{ __('ui.readiness_gap_count', ['count' => $readinessTotal]) }}</span>
</div>
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

<h2 class="section-title">{{ __('ui.change_requests') }}</h2>
@if ($change_requests->isEmpty())
    <p class="empty">{{ __('ui.export_none', ['items' => __('ui.change_requests')]) }}</p>
@else
    <table class="matrix">
        <thead>
            <tr>
                <th>{{ __('ui.code') }}</th>
                <th>{{ __('ui.title') }}</th>
                <th>{{ __('ui.requestor') }}</th>
                <th>{{ __('ui.impact_level') }}</th>
                <th>{{ __('ui.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($change_requests as $cr)
                <tr>
                    <td>{{ $cr->code ?: '—' }}</td>
                    <td>{{ $cr->title }}</td>
                    <td>{{ $cr->requestor ?: '—' }}</td>
                    <td>{{ $cr->impactLabel() }}</td>
                    <td>{{ $cr->statusLabel() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @foreach ($change_requests as $cr)
        <article class="artifact">
            <h3 class="item-title">
                @if ($cr->code)
                    <span class="artifact__code">{{ $cr->code }}</span>
                @endif
                {{ $cr->title }}
            </h3>
            <dl class="kv">
                @if ($cr->problem)
                    <dt>{{ __('ui.problem') }}</dt>
                    <dd>{{ $cr->problem }}</dd>
                @endif
                @if ($cr->proposed_change)
                    <dt>{{ __('ui.proposed_change') }}</dt>
                    <dd>{{ $cr->proposed_change }}</dd>
                @endif
                @if ($cr->impact_notes)
                    <dt>{{ __('ui.impact') }}</dt>
                    <dd>{{ $cr->impact_notes }}</dd>
                @endif
            </dl>
        </article>
    @endforeach
@endif
