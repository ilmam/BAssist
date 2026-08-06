@php
    $baseline = $strategic_baseline;
@endphp

@if ($baseline && filled($baseline->current_state))
    <h2 class="section-title">{{ __('ui.current_state') }}</h2>
    <article class="artifact strategic-baseline">
        <div class="artifact__meta">
            <span><strong>{{ __('ui.status') }}</strong>{{ $baseline->statusLabel() }}</span>
        </div>
        <p class="prose">{{ $baseline->current_state }}</p>
    </article>
@else
    <p class="empty">{{ __('ui.export_none', ['items' => __('ui.current_state')]) }}</p>
@endif

<h2 class="section-title">{{ __('ui.business_needs') }}</h2>
@if ($needs->isEmpty())
    <p class="empty">{{ __('ui.export_none', ['items' => __('ui.business_needs')]) }}</p>
@else
    @foreach ($needs as $need)
        <article class="artifact">
            <h3 class="item-title">
                @if ($need->code)
                    <span class="artifact__code">{{ $need->code }}</span>
                @endif
                {{ $need->title }}
            </h3>
            <div class="artifact__panel">
                <dl class="kv">
                    @if ($need->need_type)
                        <dt>{{ __('ui.need_type') }}</dt>
                        <dd>{{ \App\Support\NeedType::label((string) $need->need_type) }}</dd>
                    @endif
                    @if ($need->rationale)
                        <dt>{{ __('ui.rationale') }}</dt>
                        <dd>{{ $need->rationale }}</dd>
                    @endif
                    @if ($need->impact)
                        <dt>{{ __('ui.impact') }}</dt>
                        <dd>{{ $need->impact }}</dd>
                    @endif
                    @if ($need->do_nothing_consequence)
                        <dt>{{ __('ui.do_nothing_consequence') }}</dt>
                        <dd>{{ $need->do_nothing_consequence }}</dd>
                    @endif
                </dl>
            </div>
            @if ($need->description)
                <p class="prose">{{ $need->description }}</p>
            @endif
        </article>
    @endforeach
@endif
