<div class="menu menu-lg-row menu-state-bg menu-title-gray-700 menu-icon-gray-400 menu-arrow-gray-400 fw-semibold my-5 my-lg-0 align-items-stretch">
    @foreach (nav_items() as $item)
        @php($isActive = nav_item_is_active($item))

        @if (! empty($item['children']))
            <div class="menu-item menu-lg-down-accordion me-lg-1" data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-placement="bottom-start">
                <span class="menu-link py-3 {{ $isActive ? 'active' : '' }}">
                    <span class="menu-title">{{ $item['label'] }}</span>
                    <span class="menu-arrow d-lg-none"></span>
                </span>
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-rounded-0 py-lg-4 w-lg-225px">
                    @foreach ($item['children'] as $child)
                        <div class="menu-item">
                            <a class="menu-link py-3 {{ nav_item_is_active($child) ? 'active' : '' }}"
                                href="{{ route($child['route']) }}">
                                <span class="menu-title">{{ $child['label'] }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="menu-item me-lg-1">
                <a class="menu-link py-3 {{ $isActive ? 'active' : '' }}"
                    href="{{ route($item['route']) }}">
                    <span class="menu-title">{{ $item['label'] }}</span>
                </a>
            </div>
        @endif
    @endforeach
</div>
