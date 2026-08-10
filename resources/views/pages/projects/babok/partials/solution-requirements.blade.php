@if ($functional_requirements->isEmpty() && ($non_functional_requirements ?? collect())->isEmpty() && $constraints->isEmpty() && $business_rules->isEmpty())
    <p class="empty">{{ __('ui.export_none', ['items' => __('ui.solution_requirements')]) }}</p>
@endif

@if ($functional_requirements->isNotEmpty())
    <h2 class="section-title">{{ __('ui.functional_requirements') }}</h2>
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

@if (($non_functional_requirements ?? collect())->isNotEmpty())
    <h2 class="section-title">{{ __('ui.non_functional_requirements') }}</h2>
    @foreach ($non_functional_requirements as $requirement)
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
                    <span><strong>{{ __('ui.nfr_category') }}</strong>{{ $requirement->categoryLabel() }}</span>
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
                    <dt>{{ __('ui.description') }}</dt>
                    <dd>{{ $requirement->description ?: '—' }}</dd>
                    @if (filled($requirement->acceptance_criteria))
                        <dt>{{ __('ui.acceptance_criteria') }}</dt>
                        <dd>{{ $requirement->acceptance_criteria }}</dd>
                    @endif
                </dl>
            </div>
        </article>
    @endforeach
@endif

@if ($constraints->isNotEmpty())
    <h2 class="section-title section-title--break">{{ __('ui.constraints') }}</h2>
    @foreach ($constraints as $item)
        <article class="artifact">
            <h3 class="item-title">{{ $item->title }}</h3>
            @if ($item->description)
                <p class="prose">{{ $item->description }}</p>
            @endif
        </article>
    @endforeach
@endif

@if ($business_rules->isNotEmpty())
    <h2 class="section-title">{{ __('ui.business_rules') }}</h2>
    @foreach ($business_rules as $item)
        <article class="artifact">
            <h3 class="item-title">{{ $item->title }}</h3>
            @if ($item->description)
                <p class="prose">{{ $item->description }}</p>
            @endif
        </article>
    @endforeach
@endif
