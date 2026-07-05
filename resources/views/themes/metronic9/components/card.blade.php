<div class="kt-card {{ $class }}" data-id="{{ $id }}">
    <div class="kt-card-header flex items-center justify-between gap-2.5">
        <h3 class="kt-card-title">{{ $title }}</h3>
        @if (($toolbar ?? '') != '')
            <div class="kt-card-toolbar flex items-center gap-1">
                {{ $toolbar }}
            </div>
        @endif
    </div>
    @if (($slot ?? '') != '')
        <div class="kt-card-body border-t border-border p-5 lg:p-7.5">
            {{ $slot }}
        </div>
    @endif
    @if (($footer ?? '') != '')
        <div class="kt-card-footer flex justify-end gap-2.5 border-t border-border p-5 lg:p-7.5">
            {{ $footer }}
        </div>
    @endif
</div>
