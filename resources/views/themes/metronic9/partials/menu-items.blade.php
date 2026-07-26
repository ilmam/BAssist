@foreach ($items as $item)
    @php
        $isActive = nav_item_is_active($item);
        $isOpen = nav_item_is_open($item);
        $hasChildren = ! empty($item['children']);
    @endphp

    @if ($hasChildren)
        <div class="kt-menu-item {{ $isOpen ? 'active show' : '' }}"
            data-kt-menu-item-toggle="accordion"
            data-kt-menu-item-trigger="click">
            <div class="kt-menu-link flex items-center grow border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px] {{ $isActive ? 'kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg' : 'hover:bg-accent/60 hover:rounded-lg' }}">
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
                <span class="kt-menu-arrow text-muted-foreground ms-auto">
                    <i class="ki-filled ki-down text-xs"></i>
                </span>
            </div>

            <div class="kt-menu-accordion gap-1 ps-[22px] {{ $isOpen ? 'show' : '' }}">
                @include('themes.metronic9.partials.menu-items', ['items' => $item['children']])
            </div>
        </div>
    @else
        <div class="kt-menu-item {{ $isActive ? 'active' : '' }}">
            <a class="kt-menu-link flex items-center grow border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px] {{ $isActive ? 'kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg' : 'hover:bg-accent/60 hover:rounded-lg' }}"
                href="{{ nav_url($item) }}">
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
    @endif
@endforeach
