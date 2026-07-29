@php
    $level = $level ?? 0;
@endphp

@foreach ($items as $item)
    @php
        $isActive = nav_item_is_active($item);
        $isOpen = nav_item_is_open($item);
        $hasChildren = ! empty($item['children']);
        $isHeading = ($item['type'] ?? null) === 'heading';
        $childGap = $level >= 2 ? 'gap-[5px]' : 'gap-[14px]';
        $bullet = 'kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary';
        $leafLinkActive = 'kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg';
    @endphp

    @if ($isHeading)
        <div class="kt-menu-item pt-2.25 pb-px">
            <span class="kt-menu-heading uppercase text-xs font-medium text-muted-foreground ps-[10px] pe-[10px]">
                {{ $item['label'] }}
            </span>
        </div>
    @elseif ($hasChildren)
        <div class="kt-menu-item kt-menu-item-accordion {{ $isOpen ? 'show' : '' }} {{ $isActive ? 'here' : '' }}"
            data-kt-menu-item-toggle="accordion"
            data-kt-menu-item-trigger="click">
            @if ($level === 0)
                <div class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                    @if (! empty($item['icon']))
                        <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                            <i class="ki-filled ki-{{ $item['icon'] }} text-lg"></i>
                        </span>
                    @endif
                    @if (! empty($item['route']))
                        <a href="{{ nav_url($item) }}"
                            onclick="event.stopPropagation()"
                            class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary grow">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                            {{ $item['label'] }}
                        </span>
                    @endif
                    <span class="kt-menu-arrow text-muted-foreground w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                        <span class="inline-flex kt-menu-item-show:hidden">
                            <i class="ki-filled ki-plus text-[11px]"></i>
                        </span>
                        <span class="hidden kt-menu-item-show:inline-flex">
                            <i class="ki-filled ki-minus text-[11px]"></i>
                        </span>
                    </span>
                </div>
                <div class="kt-menu-accordion gap-1 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-border">
                    @include('themes.metronic9.partials.menu-items', ['items' => $item['children'], 'level' => 1])
                </div>
            @else
                <div class="kt-menu-link border border-transparent grow cursor-pointer {{ $childGap }} ps-[10px] pe-[10px] py-[8px]" tabindex="0">
                    <span class="{{ $bullet }}"></span>
                    @if (! empty($item['route']))
                        <a href="{{ nav_url($item) }}"
                            onclick="event.stopPropagation()"
                            class="kt-menu-title text-2sm font-normal me-1 text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary grow">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="kt-menu-title text-2sm font-normal me-1 text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary">
                            {{ $item['label'] }}
                        </span>
                    @endif
                    <span class="kt-menu-arrow text-muted-foreground w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                        <span class="inline-flex kt-menu-item-show:hidden">
                            <i class="ki-filled ki-plus text-[11px]"></i>
                        </span>
                        <span class="hidden kt-menu-item-show:inline-flex">
                            <i class="ki-filled ki-minus text-[11px]"></i>
                        </span>
                    </span>
                </div>
                <div class="kt-menu-accordion gap-1 relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-border">
                    @include('themes.metronic9.partials.menu-items', ['items' => $item['children'], 'level' => $level + 1])
                </div>
            @endif
        </div>
    @elseif ($level === 0)
        <div class="kt-menu-item {{ $isActive ? 'active' : '' }}">
            <a class="kt-menu-link flex items-center grow border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px] {{ $leafLinkActive }}"
                href="{{ nav_url($item) }}"
                tabindex="0">
                @if (! empty($item['icon']))
                    <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                        <i class="ki-filled ki-{{ $item['icon'] }} text-lg"></i>
                    </span>
                @endif
                <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                    {{ $item['label'] }}
                </span>
            </a>
        </div>
    @else
        <div class="kt-menu-item {{ $isActive ? 'active' : '' }}">
            <a class="kt-menu-link border border-transparent items-center grow {{ $leafLinkActive }} {{ $childGap }} ps-[10px] pe-[10px] py-[8px]"
                href="{{ nav_url($item) }}"
                tabindex="0">
                <span class="{{ $bullet }}"></span>
                <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                    {{ $item['label'] }}
                </span>
            </a>
        </div>
    @endif
@endforeach
