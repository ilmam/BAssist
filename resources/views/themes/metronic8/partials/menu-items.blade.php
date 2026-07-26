@foreach ($items as $item)
    @php
        $isActive = nav_item_is_active($item);
        $isOpen = nav_item_is_open($item);
        $hasChildren = ! empty($item['children']);
    @endphp

    @if ($hasChildren)
        <div class="menu-item menu-accordion {{ $isOpen ? 'show' : '' }}" data-kt-menu-trigger="click">
            @if (! empty($item['route']))
                <a href="{{ nav_url($item) }}" class="menu-link {{ $isActive ? 'active' : '' }}" onclick="event.stopPropagation()">
                    <span class="menu-title">{{ $item['label'] }}</span>
                    <span class="menu-arrow"></span>
                </a>
            @else
                <span class="menu-link {{ $isActive ? 'active' : '' }}">
                    <span class="menu-title">{{ $item['label'] }}</span>
                    <span class="menu-arrow"></span>
                </span>
            @endif
            <div class="menu-sub menu-sub-accordion">
                @include('themes.metronic8.partials.menu-items', ['items' => $item['children']])
            </div>
        </div>
    @else
        <div class="menu-item">
            <a class="menu-link {{ $isActive ? 'active' : '' }}"
                href="{{ nav_url($item) }}">
                <span class="menu-title">{{ $item['label'] }}</span>
            </a>
        </div>
    @endif
@endforeach
