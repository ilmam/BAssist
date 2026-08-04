@php
    $baseline = $strategic_baseline;
@endphp

@if ($baseline && filled($baseline->future_state))
    <h2 class="section-title">{{ __('ui.future_state') }}</h2>
    <article class="artifact strategic-baseline">
        <div class="artifact__meta">
            <span><strong>{{ __('ui.status') }}</strong>{{ $baseline->statusLabel() }}</span>
        </div>
        <p class="prose">{{ $baseline->future_state }}</p>
    </article>
@else
    <p class="empty">{{ __('ui.export_none', ['items' => __('ui.future_state')]) }}</p>
@endif

<h2 class="section-title">{{ __('ui.business_objectives') }}</h2>
@if ($objectives->isEmpty())
    <p class="empty">{{ __('ui.export_none', ['items' => __('ui.business_objectives')]) }}</p>
@else
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
                <dl class="kv">
                    <dt>{{ __('ui.business_needs') }}</dt>
                    <dd>
                        @php
                            $linkedNeeds = $objective->businessNeeds->map(function ($need) {
                                $label = $need->title;
                                if ($need->code) {
                                    $label = $need->code.' '.$label;
                                }

                                return $label;
                            })->filter()->values();
                        @endphp
                        {{ $linkedNeeds->isNotEmpty() ? $linkedNeeds->implode('; ') : '—' }}
                    </dd>
                    @if ($objective->success_measure)
                        <dt>{{ __('ui.success_measure') }}</dt>
                        <dd>{{ $objective->success_measure }}</dd>
                    @endif
                    @if ($objective->potential_value)
                        <dt>{{ __('ui.potential_value') }}</dt>
                        <dd>{{ $objective->potential_value }}</dd>
                    @endif
                </dl>
            </div>
            @if ($objective->description)
                <p class="prose">{{ $objective->description }}</p>
            @endif
        </article>
    @endforeach
@endif
