@php
    $resolvedSize = match ($size !== '' ? $size : 'full') {
        'xl' => 'full',
        'md' => 'lg',
        'sheet' => 'end',
        'fs', 'modal-fullscreen' => 'fullscreen',
        default => ($size !== '' ? $size : 'full'),
    };
@endphp
<div class="modal-header">
    <h3 class="modal-title">{{ $title }}</h3>
    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close">
        <i class="fa fa-times"></i>
    </button>
</div>

<div class="modal-body" data-ui-container data-modal-size="{{ $resolvedSize }}">
    {{ $slot }}
</div>

@isset($footer)
    <div class="modal-footer">
        {{ $footer }}
    </div>
@endisset
