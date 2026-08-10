@php
    $acceptanceRows = $pack['acceptance_rows'] ?? [];
@endphp

@if ($acceptanceRows === [] && $features === [])
    <p class="empty">{{ __('ui.acceptance_plan_empty') }}</p>
@else
    @if ($acceptanceRows !== [])
        <h2 class="section-title">{{ __('ui.acceptance_plan') }}</h2>
        <p class="prose">{{ __('ui.babok_doc_acceptance_criteria_note') }}</p>
        <table class="matrix">
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
                @foreach ($acceptanceRows as $row)
                    <tr>
                        <td>
                            {{ $row['test_id'] ?? '' }}
                            @if (($row['source'] ?? '') === 'fr')
                                <span class="artifact__code">{{ __('ui.acceptance_plan_source_fr') }}</span>
                            @elseif (($row['source'] ?? '') === 'bdd')
                                <span class="artifact__code">{{ __('ui.acceptance_plan_source_bdd') }}</span>
                            @endif
                        </td>
                        <td>
                            @if (! empty($row['feature_code']))
                                <span class="artifact__code">{{ $row['feature_code'] }}</span>
                            @endif
                            {{ $row['feature_title'] ?? '' }}
                        </td>
                        <td>{{ ($row['rule'] ?? '') !== '' ? $row['rule'] : '—' }}</td>
                        <td>{{ $row['scenario_title'] ?? '—' }}</td>
                        <td>{{ $row['type'] ?? '—' }}</td>
                        <td>{{ $row['status'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($features !== [])
        <h2 class="section-title">{{ __('ui.features') }}</h2>
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
@endif
