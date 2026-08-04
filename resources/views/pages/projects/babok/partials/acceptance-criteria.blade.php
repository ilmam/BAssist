@if ($features === [])
    <p class="empty">{{ __('ui.export_none', ['items' => __('ui.features')]) }}</p>
@else
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
