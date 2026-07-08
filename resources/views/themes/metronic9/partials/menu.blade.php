<div class="kt-menu flex flex-col grow gap-1" data-kt-menu="true" data-kt-menu-accordion-expand-all="false">
    @foreach (nav_items() as $item)
        @php($isActive = nav_item_is_active($item))

        @if (! empty($item['children']))
            <div class="kt-menu-item {{ $isActive ? 'active' : '' }}" data-kt-menu-item-toggle="accordion" data-kt-menu-item-trigger="click">
                <div class="kt-menu-link flex items-center grow border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px] {{ $isActive ? 'kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg' : 'hover:bg-accent/60 hover:rounded-lg' }}">
                    @if (! empty($item['icon']))
                        <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                            <i class="ki-filled ki-{{ $item['icon'] }} text-lg"></i>
                        </span>
                    @endif
                    <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                        {{ $item['label'] }}
                    </span>
                    <span class="kt-menu-arrow text-muted-foreground ms-auto">
                        <i class="ki-filled ki-down text-xs"></i>
                    </span>
                </div>

                <div class="kt-menu-accordion gap-1 ps-[30px] {{ $isActive ? 'show' : '' }}">
                    @foreach ($item['children'] as $child)
                        <div class="kt-menu-item {{ nav_item_is_active($child) ? 'active' : '' }}">
                            <a class="kt-menu-link flex items-center grow border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px] {{ nav_item_is_active($child) ? 'kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg' : 'hover:bg-accent/60 hover:rounded-lg' }}"
                                href="{{ route($child['route']) }}">
                                <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                                    {{ $child['label'] }}
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="kt-menu-item {{ $isActive ? 'active' : '' }}">
                <a class="kt-menu-link flex items-center grow border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px] {{ $isActive ? 'kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg' : 'hover:bg-accent/60 hover:rounded-lg' }}"
                    href="{{ route($item['route']) }}">
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
</div>
