<div class="kt-menu flex flex-col grow gap-1" data-kt-menu="true" data-kt-menu-accordion-expand-all="false">
    @foreach (nav_items() as $item)
        <div class="kt-menu-item {{ nav_is_active($item['route']) ? 'active' : '' }}">
            <a class="kt-menu-link flex items-center grow border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px] {{ nav_is_active($item['route']) ? 'kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg' : 'hover:bg-accent/60 hover:rounded-lg' }}"
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
    @endforeach
</div>
