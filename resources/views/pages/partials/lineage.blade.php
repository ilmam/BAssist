{{--
    Lineage rail + Next step (#3). Full page = design A; $inModal = compact design C.
    Data: SpineCascadeService::lineage() via $lineage.
--}}
@php
    $inModal = (bool) ($inModal ?? false);
    $steps = $lineage['steps'] ?? [];
    $next = $lineage['next'] ?? null;
    $others = $lineage['others'] ?? [];
    $quick = $lineage['quick'] ?? [];
    $also = $lineage['also'] ?? [];
    $complete = (int) ($lineage['complete'] ?? 0);
    $total = (int) ($lineage['total'] ?? 0);
    $isComplete = $total > 0 && $complete >= $total;
    $linkHref = fn (array $link) => $inModal ? ($link['modal_url'] ?? $link['url'] ?? '#') : ($link['url'] ?? '#');
@endphp

@if ($steps !== [])
    @if ($inModal)
        {{-- C · compact chain --}}
        <div class="ba-lineage-compact" aria-label="{{ __('ui.lineage_title') }}">
            <ol class="ba-lineage-compact__chain">
                @foreach ($steps as $step)
                    @continue(in_array($step['state'], ['later', 'optional', 'blocked'], true))
                    <li class="ba-lineage-compact__item ba-lineage-compact__item--{{ $step['state'] }}" title="{{ $step['name'] }}">
                        @if ($step['state'] === 'done' && ! empty($step['link']['url']))
                            <a href="{{ $linkHref($step['link']) }}" class="ba-code-chip js-open-modal" data-modal-url="{{ $step['link']['modal_url'] ?? $step['link']['url'] }}" data-modal-nav="off">{{ $step['link']['code'] ?? $step['name'] }}</a>
                        @elseif ($step['state'] === 'done')
                            <x-status-badge tone="success">{{ $step['note'] }}</x-status-badge>
                        @elseif ($step['state'] === 'current')
                            <span class="ba-code-chip ba-code-chip--current" aria-current="true">{{ $step['link']['code'] ?? $step['name'] }}</span>
                        @else
                            <x-status-badge tone="warning">{{ $step['name'] }} · {{ __('ui.lineage_not_linked') }}</x-status-badge>
                        @endif
                    </li>
                @endforeach
            </ol>
            @if ($next)
                <div class="ba-lineage-compact__next">
                    <span><strong>{{ __('ui.lineage_next_step') }}:</strong> {{ $next['title'] }}</span>
                    @if ($next['action'])
                        <a href="{{ $next['action']['url'] }}" class="{{ ui_btn_classes('primary', 'sm') }} js-open-modal" data-modal-url="{{ $next['action']['url'] }}">{{ $next['action']['label'] }}</a>
                    @endif
                </div>
            @endif
            @if ($quick !== [])
                <div class="ba-lineage-compact__quick">
                    @foreach ($quick as $action)
                        <a href="{{ $action['url'] }}" class="{{ ui_btn_classes('outline', 'sm') }} js-open-modal" data-modal-url="{{ $action['url'] }}"><i class="ki-filled ki-plus"></i>{{ $action['label'] }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        {{-- A · lineage rail --}}
        <section class="ba-lineage" aria-labelledby="ba-lineage-title">
            <header class="ba-lineage__head">
                <div class="ba-lineage__heading">
                    <h2 id="ba-lineage-title">{{ __('ui.lineage_title') }}</h2>
                    <span>{{ __('ui.lineage_subtitle') }}</span>
                </div>
                @if ($isComplete)
                    <x-status-badge tone="success">{{ __('ui.lineage_complete') }}</x-status-badge>
                @else
                    <x-status-badge tone="warning">{{ __('ui.lineage_progress', ['done' => $complete, 'total' => $total]) }}</x-status-badge>
                @endif
            </header>

            <ol class="ba-lineage__rail">
                @foreach ($steps as $step)
                    <li class="ba-lineage__step ba-lineage__step--{{ $step['state'] }}" @if ($step['state'] === 'current') aria-current="true" @endif>
                        @if ($step['state'] === 'done' && ! empty($step['link']['url']))
                            <a class="ba-lineage__node" href="{{ $step['link']['url'] }}">
                                <span class="ba-lineage__level">{{ $step['level'] }} · {{ $step['name'] }}</span>
                                <x-code-chip :code="$step['link']['code'] ?? null" />
                                <span class="ba-lineage__title">{{ $step['link']['title'] ?? $step['link']['label'] ?? '' }}</span>
                            </a>
                        @else
                            <div class="ba-lineage__node">
                                <span class="ba-lineage__level">{{ $step['level'] }} · {{ $step['name'] }}</span>
                                @if ($step['state'] === 'current')
                                    <x-code-chip :code="$step['link']['code'] ?? null" />
                                    <span class="ba-lineage__title ba-lineage__title--strong">{{ $step['link']['title'] ?? '' }}</span>
                                    @if ($step['note'])
                                        <span class="ba-lineage__note">{{ $step['note'] }}</span>
                                    @endif
                                @elseif ($step['state'] === 'done')
                                    <span class="ba-lineage__title">{{ $step['note'] }}</span>
                                @elseif ($step['state'] === 'missing')
                                    <span class="ba-lineage__title ba-lineage__title--missing">{{ $step['note'] }}</span>
                                    @if ($step['action'])
                                        <a href="{{ $step['action']['url'] }}" class="ba-lineage__fix js-open-modal" data-modal-url="{{ $step['action']['url'] }}">+ {{ $step['action']['label'] }}</a>
                                    @endif
                                @elseif ($step['state'] === 'blocked')
                                    <span class="ba-lineage__title ba-lineage__title--muted">{{ $step['note'] }}</span>
                                @elseif ($step['state'] === 'optional')
                                    <span class="ba-lineage__title ba-lineage__title--muted">{{ $step['note'] ?: __('ui.lineage_optional') }}</span>
                                @else
                                    <span class="ba-lineage__title ba-lineage__title--muted">{{ __('ui.lineage_later') }}</span>
                                @endif
                            </div>
                        @endif
                    </li>
                @endforeach
            </ol>

            @if ($also !== [])
                <div class="ba-lineage__also">
                    <span>{{ __('ui.lineage_also_linked') }}:</span>
                    @foreach ($also as $link)
                        <a href="{{ $link['url'] }}" class="js-open-modal" data-modal-url="{{ $link['modal_url'] ?? $link['url'] }}" data-modal-nav="off">
                            <x-code-chip :code="$link['code'] ?? null" /> {{ $link['title'] ?? '' }}
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="ba-next-row">
            @if ($next)
                <section class="ba-next">
                    <span class="ba-next__icon" aria-hidden="true"><i class="ki-filled ki-arrow-right"></i></span>
                    <div class="ba-next__body">
                        <div class="ba-next__eyebrow">{{ __('ui.lineage_next_step') }}</div>
                        <div class="ba-next__title">{{ $next['title'] }}</div>
                        @if ($next['why'])
                            <p class="ba-next__why">{{ $next['why'] }}</p>
                        @endif
                        <div class="ba-next__actions">
                            @if ($next['action'])
                                <a href="{{ $next['action']['url'] }}" class="{{ ui_btn_classes('primary') }} js-open-modal" data-modal-url="{{ $next['action']['url'] }}">{{ $next['action']['label'] }}</a>
                            @endif
                            @if ($others !== [])
                                <details class="ba-next__more">
                                    <summary class="{{ ui_btn_classes('outline') }}">{{ trans_choice('ui.lineage_more_gaps', count($others), ['count' => count($others)]) }}</summary>
                                    <ul>
                                        @foreach ($others as $other)
                                            <li>
                                                <span>{{ $other['title'] }}</span>
                                                @if ($other['action'])
                                                    <a href="{{ $other['action']['url'] }}" class="js-open-modal" data-modal-url="{{ $other['action']['url'] }}">{{ $other['action']['label'] }}</a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    </div>
                </section>
            @else
                <section class="ba-next ba-next--done">
                    <span class="ba-next__icon" aria-hidden="true"><i class="ki-filled ki-check"></i></span>
                    <div class="ba-next__body">
                        <div class="ba-next__title">{{ __('ui.lineage_all_set_title') }}</div>
                        <p class="ba-next__why">{{ __('ui.lineage_all_set_hint') }}</p>
                    </div>
                </section>
            @endif

            @if ($quick !== [])
                <section class="ba-quick">
                    <div class="ba-next__eyebrow ba-next__eyebrow--muted">{{ __('ui.lineage_from_here') }}</div>
                    @foreach ($quick as $action)
                        <a href="{{ $action['url'] }}" class="{{ ui_btn_classes('outline') }} ba-quick__btn js-open-modal" data-modal-url="{{ $action['url'] }}">
                            <i class="ki-filled ki-{{ $action['icon'] }}"></i>{{ $action['label'] }}
                        </a>
                    @endforeach
                    <div class="ba-quick__hint">{{ __('ui.lineage_prelinked') }}</div>
                </section>
            @endif
        </div>
    @endif
@endif
