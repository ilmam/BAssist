<div class="menu menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-state-primary fw-semibold">
    @foreach (nav_items() as $item)
        @php($isActive = nav_item_is_active($item))

        @if (! empty($item['children']))
            <div class="menu-item menu-accordion {{ $isActive ? 'show' : '' }}" data-kt-menu-trigger="click">
                <span class="menu-link {{ $isActive ? 'active' : '' }}">
                    <span class="menu-title">{{ $item['label'] }}</span>
                    <span class="menu-arrow"></span>
                </span>
                <div class="menu-sub menu-sub-accordion">
                    @foreach ($item['children'] as $child)
                        <div class="menu-item">
                            <a class="menu-link {{ nav_item_is_active($child) ? 'active' : '' }}"
                                href="{{ route($child['route']) }}">
                                <span class="menu-title">{{ $child['label'] }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="menu-item">
                <a class="menu-link {{ $isActive ? 'active' : '' }}"
                    href="{{ route($item['route']) }}">
                    <span class="menu-title">{{ $item['label'] }}</span>
                </a>
            </div>
        @endif
    @endforeach
</div>
