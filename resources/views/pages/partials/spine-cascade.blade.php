@php
    $cascade = $cascade ?? null;
    $part = $part ?? 'all';
    $inModal = (bool) ($inModal ?? false);
    $showParents = in_array($part, ['all', 'before'], true);
    $showRest = in_array($part, ['all', 'after'], true);
@endphp

@if (is_array($cascade))
    @if ($showParents && ($cascade['parents'] ?? []) !== [])
        <nav class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm" aria-label="{{ __('ui.cascade_parents') }}">
            @foreach ($cascade['parents'] as $parent)
                @php
                    $href = $inModal ? ($parent['modal_url'] ?? $parent['url']) : $parent['url'];
                @endphp
                <a
                    href="{{ $href }}"
                    class="text-primary hover:underline {{ $inModal ? 'js-open-modal' : '' }}"
                    @if ($inModal) data-modal-url="{{ $parent['modal_url'] ?? $parent['url'] }}" @endif
                >{{ $parent['label'] }}</a>
                <span class="text-muted-foreground" aria-hidden="true">→</span>
            @endforeach
            <span class="font-medium text-foreground">{{ $cascade['current_label'] }}</span>
        </nav>
    @endif

    @if ($showRest)
        @if (($cascade['gaps'] ?? []) !== [])
            <section class="rounded-lg border border-border bg-muted/20 p-4 space-y-3" data-spine-cascade-gaps>
                <h3 class="text-sm font-semibold text-foreground">{{ __('ui.cascade_missing') }}</h3>
                <ul class="space-y-2">
                    @foreach ($cascade['gaps'] as $gap)
                        <li class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 space-y-1">
                                <p class="text-sm text-foreground">{{ $gap['label'] }}</p>
                                @if (! empty($gap['links']))
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($gap['links'] as $link)
                                            @php
                                                $href = $inModal ? ($link['modal_url'] ?? $link['url']) : $link['url'];
                                            @endphp
                                            <a
                                                href="{{ $href }}"
                                                class="kt-badge kt-badge-sm kt-badge-outline {{ $inModal ? 'js-open-modal' : '' }}"
                                                @if ($inModal) data-modal-url="{{ $link['modal_url'] ?? $link['url'] }}" @endif
                                            >{{ $link['label'] }}</a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            @if (
                                ! empty($gap['action_url'])
                                && filled($gap['action_label'])
                                && (empty($gap['action_model']) || entity_can($gap['action_model'], $gap['action_ability'] ?? 'create'))
                            )
                                <x-button
                                    type="link"
                                    href="{{ $gap['action_url'] }}"
                                    color="primary"
                                    size="sm"
                                    class="js-open-modal shrink-0"
                                    data-modal-url="{{ $gap['action_url'] }}"
                                >{{ $gap['action_label'] }}</x-button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @foreach ($cascade['groups'] ?? [] as $group)
            @php
                $canAdd = entity_can($group['add_model'], 'create');
                $canView = entity_can($group['add_model'], 'view');
            @endphp
            @if ($canAdd || $canView)
                <section class="space-y-3" data-spine-cascade-group="{{ $group['key'] }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <h3 class="text-base font-semibold text-foreground">{{ $group['heading'] }}</h3>
                        @if ($canAdd && ! empty($group['add_url']))
                            <x-button
                                type="link"
                                href="{{ $group['add_url'] }}"
                                color="primary"
                                class="js-open-modal"
                                data-modal-url="{{ $group['add_url'] }}"
                            >{{ $group['add_label'] }}</x-button>
                        @endif
                    </div>
                    @if ($canView && ($group['items'] ?? []) !== [])
                        <ul class="divide-y divide-border rounded-lg border border-border">
                            @foreach ($group['items'] as $item)
                                @php
                                    $href = $inModal ? ($item['modal_url'] ?? $item['url']) : $item['url'];
                                @endphp
                                <li>
                                    <a
                                        href="{{ $href }}"
                                        class="flex flex-wrap items-baseline justify-between gap-2 px-4 py-2.5 hover:bg-muted/40 {{ $inModal ? 'js-open-modal' : '' }}"
                                        @if ($inModal) data-modal-url="{{ $item['modal_url'] ?? $item['url'] }}" @endif
                                    >
                                        <span class="text-sm text-foreground">{{ $item['label'] }}</span>
                                        @if (! empty($item['meta']))
                                            <span class="text-xs text-muted-foreground">{{ $item['meta'] }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @elseif ($canAdd || $canView)
                        <p class="text-sm text-muted-foreground">{{ $group['empty'] }}</p>
                    @endif
                </section>
            @endif
        @endforeach
    @endif
@endif
