<div class="card card-flush shadow-sm {{ $class }}" data-id="{{ $id }}">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
        <div class="card-toolbar">
            {{ $toolbar ?? '' }}
        </div>
    </div>
    @if (($slot ?? '') != '')
        <div class="card-body border-top p-9">
            {{ $slot }}
        </div>
    @endif

    @if (($footer ?? '') != '')
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            {{ $footer }}
        </div>
    @endif
</div>