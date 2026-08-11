@php
    // Bootstrap dialog size: sm | lg | xl | fullscreen (modal-fullscreen).
    $resolvedSize = match ($size !== '' ? $size : '') {
        'fs', 'modal-fullscreen' => 'fullscreen',
        default => $size,
    };
    $dialogClass = match (true) {
        $resolvedSize === 'fullscreen' => 'modal-fullscreen',
        $resolvedSize !== '' => 'modal-'.$resolvedSize,
        default => 'modal-lg',
    };
@endphp
<div class="modal fade" tabindex="-1" id="{{ $id }}">
    <div class="modal-dialog {{ $dialogClass }}" data-modal-dialog>
        <div class="modal-content" data-modal-container></div>
    </div>
</div>
