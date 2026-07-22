@php
    $isSheet = in_array($size, ['end', 'sheet'], true);
@endphp
<div
    data-modal-size="{{ $size !== '' ? $size : 'lg' }}"
    @class([
        'flex flex-col min-h-0 h-full' => $isSheet,
    ])
>
    <div @class(['kt-modal-header', 'shrink-0' => $isSheet])>
        <h3 class="kt-modal-title">{{ $title }}</h3>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true" type="button" aria-label="Close">
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div @class([
        'kt-modal-body',
        'flex-1 min-h-0 overflow-y-auto overscroll-contain' => $isSheet,
    ])>
        {{ $slot }}
    </div>

    @isset($footer)
        <div @class(['kt-modal-footer justify-end gap-2.5', 'shrink-0' => $isSheet])>
            {{ $footer }}
        </div>
    @endisset
</div>
