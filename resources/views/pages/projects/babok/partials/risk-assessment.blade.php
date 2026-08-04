@php
    $risks = $risks ?? collect();
@endphp

@if ($risks->isEmpty())
    <p class="empty">{{ __('ui.export_none', ['items' => __('ui.risks')]) }}</p>
@else
    @foreach ($risks as $risk)
        @php
            $band = $risk->score_band;
            $critical = $risk->isCritical();
            $gap = $risk->hasCoverageGap();
            $classes = ['artifact'];
            if ($critical) {
                $classes[] = 'artifact--risk-critical';
            }
            if ($gap) {
                $classes[] = 'artifact--coverage-gap';
            }
        @endphp
        <article class="{{ implode(' ', $classes) }}">
            <h3 class="item-title">
                @if ($risk->code)
                    <span class="artifact__code">{{ $risk->code }}</span>
                @endif
                {{ $risk->title }}
                <span class="risk-score-chip risk-score-chip--{{ $band }}">{{ $risk->score_label }}</span>
            </h3>
            <div class="artifact__panel">
                <div class="artifact__meta">
                    <span><strong>{{ __('ui.risk_category') }}</strong>{{ $risk->categoryLabel() }}</span>
                    <span><strong>{{ __('ui.risk_likelihood') }}</strong>{{ $risk->likelihoodLabel() }}</span>
                    <span><strong>{{ __('ui.impact') }}</strong>{{ $risk->impactLabel() }}</span>
                    <span><strong>{{ __('ui.status') }}</strong>{{ $risk->statusLabel() }}</span>
                    @if ($risk->owner)
                        <span><strong>{{ __('ui.risk_owner') }}</strong>{{ $risk->owner }}</span>
                    @endif
                </div>
                <dl class="kv">
                    @if ($risk->description)
                        <dt>{{ __('ui.description') }}</dt>
                        <dd>{{ $risk->description }}</dd>
                    @endif
                    <dt>{{ __('ui.risk_response') }}</dt>
                    <dd>{{ $risk->responseLabel() }}</dd>
                    <dt>{{ $risk->response === \App\Support\RiskResponse::ACCEPT ? __('ui.risk_treatment').' / '.__('ui.rationale') : __('ui.risk_treatment') }}</dt>
                    <dd>{{ filled($risk->treatment) ? $risk->treatment : '—' }}</dd>
                    @if ($risk->trigger)
                        <dt>{{ __('ui.risk_trigger') }}</dt>
                        <dd>{{ $risk->trigger }}</dd>
                    @endif
                    @if ($risk->related_to)
                        <dt>{{ __('ui.related_to') }}</dt>
                        <dd>{{ $risk->related_to }}</dd>
                    @endif
                    @if ($risk->source)
                        <dt>{{ __('ui.source') }}</dt>
                        <dd>{{ $risk->source }}</dd>
                    @endif
                </dl>
                @if ($gap)
                    <p class="risk-gap-flag">{{ __('ui.risk_coverage_gap') }}</p>
                @endif
            </div>
        </article>
    @endforeach
@endif
