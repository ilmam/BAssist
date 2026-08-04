@if ($stakeholder_needs->isEmpty())
    <p class="empty">{{ __('ui.export_none', ['items' => __('ui.stakeholder_needs')]) }}</p>
@else
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
