<div class="kt-card {{ $class }}" data-id="{{ $id }}">
    <div class="kt-card-header flex items-center justify-between gap-2.5">
        <h3 class="kt-card-title">{{ $title }}</h3>
        @if (($toolbar ?? '') != '')
            <div class="kt-card-toolbar flex items-center gap-1">
                {{ $toolbar }}
            </div>
        @endif
    </div>
    {{ $slot }}
</div>
