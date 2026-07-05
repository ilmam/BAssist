<div class="menu menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-state-primary fw-semibold">
    @foreach (nav_items() as $item)
        <div class="menu-item">
            <a class="menu-link {{ nav_is_active($item['route']) ? 'active' : '' }}"
                href="{{ route($item['route']) }}">
                <span class="menu-title">{{ $item['label'] }}</span>
            </a>
        </div>
    @endforeach
</div>
