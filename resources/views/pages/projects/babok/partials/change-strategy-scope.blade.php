@php
    $baseline = $strategic_baseline;
    $inScopeItems = $scope_items->where('direction', \App\Support\ScopeItemDirection::IN)->values();
    $outScopeItems = $scope_items->where('direction', \App\Support\ScopeItemDirection::OUT)->values();
@endphp

@if ($baseline && filled($baseline->change_strategy))
    <h2 class="section-title">{{ __('ui.change_strategy') }}</h2>
    <article class="artifact strategic-baseline">
        <div class="artifact__meta">
            <span><strong>{{ __('ui.status') }}</strong>{{ $baseline->statusLabel() }}</span>
        </div>
        <p class="prose">{{ $baseline->change_strategy }}</p>
    </article>
@else
    <p class="empty">{{ __('ui.export_none', ['items' => __('ui.change_strategy')]) }}</p>
@endif

@if ($assumptions->isNotEmpty())
    <h2 class="section-title">{{ __('ui.assumptions') }}</h2>
    @foreach ($assumptions as $item)
        <article class="artifact">
            <h3 class="item-title">{{ $item->title }}</h3>
            <div class="artifact__meta">
                <span><strong>{{ __('ui.status') }}</strong>{{ $item->statusLabel() }}</span>
            </div>
            @if ($item->description)
                <p class="prose">{{ $item->description }}</p>
            @endif
        </article>
    @endforeach
@endif

@if ($scope_items->isNotEmpty())
    <h2 class="section-title">{{ __('ui.scope_items') }}</h2>
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
                    @forelse ($inScopeItems as $item)
                        <article class="artifact">
                            <h3 class="item-title">{{ $item->title }}</h3>
                            @if ($item->description)
                                <p class="prose">{{ $item->description }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="empty">—</p>
                    @endforelse
                </td>
                <td>
                    @forelse ($outScopeItems as $item)
                        <article class="artifact">
                            <h3 class="item-title">{{ $item->title }}</h3>
                            @if ($item->description)
                                <p class="prose">{{ $item->description }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="empty">—</p>
                    @endforelse
                </td>
            </tr>
        </tbody>
    </table>
@endif
