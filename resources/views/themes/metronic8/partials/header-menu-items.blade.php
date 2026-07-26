@foreach ($items as $item)
    @php
        $isActive = nav_item_is_active($item);
        $isOpen = nav_item_is_open($item);
        $hasChildren = ! empty($item['children']);
    @endphp

    @if ($hasChildren)
        <div class="menu-item menu-lg-down-accordion {{ $depth === 0 ? 'me-lg-1' : '' }}"
            data-kt-menu-trigger="{default:'click', lg: 'hover'}"
            data-kt-menu-placement="{{ $depth === 0 ? 'bottom-start' : 'right-start' }}">
            @if (! empty($item['route']))
                <a href="{{ nav_url($item) }}" class="menu-link py-3 {{ $isActive || $isOpen ? 'active' : '' }}">
                    <span class="menu-title">{{ $item['label'] }}</span>
                    <span class="menu-arrow {{ $depth === 0 ? 'd-lg-none' : '' }}"></span>
                </a>
            @else
                <span class="menu-link py-3 {{ $isActive || $isOpen ? 'active' : '' }}">
                    <span class="menu-title">{{ $item['label'] }}</span>
                    <span class="menu-arrow {{ $depth === 0 ? 'd-lg-none' : '' }}"></span>
                </span>
            @endif
            <div class="menu-sub {{ $depth === 0 ? 'menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-rounded-0 py-lg-4 w-lg-225px' : 'menu-sub-lg-dropdown menu-rounded-0 py-lg-4 w-lg-225px' }}">
                @include('themes.metronic8.partials.header-menu-items', [
                    'items' => $item['children'],
                    'depth' => $depth + 1,
                ])
            </div>
        </div>
    @else
        <div class="menu-item {{ $depth === 0 ? 'me-lg-1' : '' }}">
            <a class="menu-link py-3 {{ $isActive ? 'active' : '' }}"
                href="{{ nav_url($item) }}">
                <span class="menu-title">{{ $item['label'] }}</span>
            </a>
        </div>
    @endif
@endforeach
