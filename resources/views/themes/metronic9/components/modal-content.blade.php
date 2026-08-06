@php
    // Legacy aliases: xl → Large (full), md → Medium (lg), sheet → Side (end).
    $resolvedSize = match ($size !== '' ? $size : 'full') {
        'xl' => 'full',
        'md' => 'lg',
        'sheet' => 'end',
        default => ($size !== '' ? $size : 'full'),
    };
    $isSheet = in_array($resolvedSize, ['end', 'sheet'], true);
    // Unified size glyphs: same frame icon at S / M / L scale; side stays distinct.
    $sizeModes = [
        'sm' => [
            'icon' => 'ki-frame',
            'icon_class' => 'text-[10px]',
            'label' => __('ui.modal_size_small'),
        ],
        'lg' => [
            'icon' => 'ki-frame',
            'icon_class' => 'text-xs',
            'label' => __('ui.modal_size_medium'),
        ],
        'full' => [
            'icon' => 'ki-frame',
            'icon_class' => 'text-base',
            'label' => __('ui.modal_size_large'),
        ],
        'end' => [
            'icon' => 'ki-exit-right',
            'icon_class' => '',
            'label' => __('ui.modal_size_side'),
        ],
    ];
@endphp
<div
    data-modal-size="{{ $resolvedSize }}"
    data-ui-container
    @class([
        'flex flex-col min-h-0 h-full' => $isSheet,
    ])
>
    <div @class(['kt-modal-header', 'shrink-0' => $isSheet])>
        <h3 class="kt-modal-title">{{ $title }}</h3>
        <div class="flex items-center gap-1.5 shrink-0">
            <div
                class="flex items-center gap-0.5 rounded-md border border-border p-0.5"
                role="group"
                aria-label="{{ __('ui.modal_size') }}"
                data-modal-size-switcher
            >
                @foreach ($sizeModes as $mode => $meta)
                    @php
                        $isActive = $mode === 'end'
                            ? $isSheet
                            : $resolvedSize === $mode;
                    @endphp
                    <button
                        type="button"
                        class="kt-btn kt-btn-sm kt-btn-icon {{ $isActive ? 'kt-btn-secondary' : 'kt-btn-ghost' }}"
                        data-modal-size-set="{{ $mode }}"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                        title="{{ $meta['label'] }}"
                        aria-label="{{ $meta['label'] }}"
                    >
                        <i class="ki-filled {{ $meta['icon'] }} {{ $meta['icon_class'] }}"></i>
                    </button>
                @endforeach
            </div>
            <button
                type="button"
                class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0"
                data-modal-backdrop-toggle
                aria-pressed="false"
                title="{{ __('ui.modal_backdrop_show_page') }}"
                aria-label="{{ __('ui.modal_backdrop_show_page') }}"
            >
                <i class="ki-filled ki-eye" data-modal-backdrop-icon></i>
            </button>
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true" type="button" aria-label="Close">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
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
