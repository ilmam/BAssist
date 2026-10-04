@php
    $cascade = $cascade ?? null;
    $part = $part ?? 'all';
    $inModal = (bool) ($inModal ?? false);
    $showParents = in_array($part, ['all', 'before'], true);
    $showRest = in_array($part, ['all', 'after'], true);
@endphp

@if (is_array($cascade))
    @php $hasLineage = ! empty($cascade['lineage']['steps'] ?? []); @endphp
    @if ($showParents && $hasLineage)
        @include('pages.partials.lineage', ['lineage' => $cascade['lineage'], 'inModal' => $inModal])
    @elseif ($showParents && ($cascade['parents'] ?? []) !== [])
        @php
            $currentCode = null;
            $currentTitle = $cascade['current_label'] ?? '';
            if (str_contains($currentTitle, ' — ')) {
                [$currentCode, $currentTitle] = explode(' — ', $currentTitle, 2);
            }
        @endphp
        <nav aria-label="{{ __('ui.cascade_parents') }}">
            <ol class="spine-breadcrumb">
                @foreach ($cascade['parents'] as $parent)
                    @php
                        $href = $inModal ? ($parent['modal_url'] ?? $parent['url']) : $parent['url'];
                    @endphp
                    <li>
                        <a
                            href="{{ $href }}"
                            class="spine-breadcrumb__link {{ $inModal ? 'js-open-modal' : '' }}"
                            title="{{ $parent['label'] }}"
                            @if ($inModal) data-modal-url="{{ $parent['modal_url'] ?? $parent['url'] }}" @endif
                        >
                            @if (! empty($parent['code']))
                                <span class="spine-breadcrumb__code">{{ $parent['code'] }}</span>
                            @endif
                            @if (! empty($parent['title']))
                                <span class="spine-breadcrumb__title">{{ $parent['title'] }}</span>
                            @elseif (empty($parent['code']))
                                <span class="spine-breadcrumb__title">{{ $parent['label'] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
                <li aria-current="page">
                    <span class="spine-breadcrumb__current" title="{{ $cascade['current_label'] }}">
                        @if ($currentCode)
                            <span class="spine-breadcrumb__code">{{ $currentCode }}</span>
                        @endif
                        @if ($currentTitle !== '')
                            <span class="spine-breadcrumb__title">{{ $currentTitle }}</span>
                        @endif
                    </span>
                </li>
            </ol>
        </nav>
    @endif

    @if ($showRest)
        @if (! $hasLineage && ($cascade['gaps'] ?? []) !== [])
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
                $addActions = [];
                foreach ($group['add_actions'] ?? [] as $action) {
                    if (
                        ! empty($action['url'])
                        && ! empty($action['model'])
                        && entity_can($action['model'], 'create')
                    ) {
                        $addActions[] = $action;
                    }
                }
                $canAddSingle = $addActions === []
                    && ! empty($group['add_url'])
                    && ! empty($group['add_model'])
                    && entity_can($group['add_model'], 'create');
                $viewModels = array_values(array_unique(array_filter(array_merge(
                    array_column($group['add_actions'] ?? [], 'model'),
                    array_column($group['items'] ?? [], 'model'),
                    [$group['add_model'] ?? ''],
                ))));
                $canView = false;
                foreach ($viewModels as $viewModel) {
                    if (entity_can($viewModel, 'view')) {
                        $canView = true;
                        break;
                    }
                }
                $visibleItems = [];
                foreach ($group['items'] ?? [] as $item) {
                    $itemModel = $item['model'] ?? ($group['add_model'] ?? '');
                    if ($itemModel === '' || entity_can($itemModel, 'view')) {
                        $visibleItems[] = $item;
                    }
                }
            @endphp
            @if ($canAddSingle || $addActions !== [] || $canView)
                <x-section :title="$group['heading']" icon="{{ $group['icon'] ?? 'element-11' }}" :meta="$canView ? (string) count($visibleItems) : null" data-spine-cascade-group="{{ $group['key'] }}" key="related-{{ $group['key'] }}">
                    @if (count($addActions) === 1 || ($addActions === [] && $canAddSingle))
                        @php $add = $addActions[0] ?? ['url' => $group['add_url'], 'label' => $group['add_label']]; @endphp
                        <x-slot:actions>
                            <a href="{{ $add['url'] }}" class="{{ ui_btn_classes('outline', 'sm') }} js-open-modal" data-modal-url="{{ $add['url'] }}"><i class="ki-filled ki-plus"></i>{{ $add['label'] }}</a>
                        </x-slot:actions>
                    @endif
                    @if (count($addActions) > 1)
                        <div class="flex justify-end mb-3">
                            <details class="spine-cascade-add">
                                <summary>
                                    {{ __('ui.cascade_add') }}
                                    <i class="ki-filled ki-down text-xs"></i>
                                </summary>
                                <div class="spine-cascade-add__menu">
                                    @foreach ($addActions as $action)
                                        <a
                                            href="{{ $action['url'] }}"
                                            class="spine-cascade-add__item js-open-modal"
                                            data-modal-url="{{ $action['url'] }}"
                                        >{{ $action['label'] }}</a>
                                    @endforeach
                                </div>
                            </details>
                        </div>
                    @endif
                    @if ($canView && $visibleItems !== [])
                        <ul class="divide-y divide-border rounded-lg border border-border">
                            @foreach ($visibleItems as $item)
                                @php
                                    $href = $inModal ? ($item['modal_url'] ?? $item['url']) : $item['url'];
                                @endphp
                                <li>
                                    <a
                                        href="{{ $href }}"
                                        class="flex flex-wrap items-baseline justify-between gap-2 px-4 py-2.5 hover:bg-muted/40 {{ $inModal ? 'js-open-modal' : '' }}"
                                        @if ($inModal) data-modal-url="{{ $item['modal_url'] ?? $item['url'] }}" @endif
                                    >
                                        <span class="flex min-w-0 items-baseline gap-2">
                                            @if (! empty($item['kind']))
                                                <span class="kt-badge kt-badge-sm kt-badge-outline shrink-0">{{ $item['kind'] }}</span>
                                            @endif
                                            <span class="text-sm text-foreground">{{ $item['label'] }}</span>
                                        </span>
                                        @if (! empty($item['meta']))
                                            <span class="text-xs text-muted-foreground">{{ $item['meta'] }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @elseif ($canAddSingle || $addActions !== [] || $canView)
                        <p class="text-sm text-muted-foreground">{{ $group['empty'] }}</p>
                    @endif
                </x-section>
            @endif
        @endforeach
    @endif
@endif
