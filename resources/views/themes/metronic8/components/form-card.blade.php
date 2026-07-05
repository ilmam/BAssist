<div class="card card-flush shadow-sm {{ $class }}" data-id="{{ $id }}">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
        <div class="card-toolbar">
            {{ $toolbar ?? '' }}
        </div>
    </div>
    {{ $slot }}
</div>