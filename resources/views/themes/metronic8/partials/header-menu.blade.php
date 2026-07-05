<div class="menu menu-lg-row menu-state-bg menu-title-gray-700 menu-icon-gray-400 menu-arrow-gray-400 fw-semibold my-5 my-lg-0 align-items-stretch">
    @foreach (nav_items() as $item)
        <div class="menu-item me-lg-1">
            <a class="menu-link py-3 {{ nav_is_active($item['route']) ? 'active' : '' }}"
                href="{{ route($item['route']) }}">
                <span class="menu-title">{{ $item['label'] }}</span>
            </a>
        </div>
    @endforeach
</div>
