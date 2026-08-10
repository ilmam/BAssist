@if (config('ui.modal_record_nav', true))
    @php
        $theme = function_exists('ui_theme') ? ui_theme() : 'metronic9';
        $btnClass = $theme === 'metronic9'
            ? 'kt-btn kt-btn-sm kt-btn-outline'
            : 'btn btn-sm btn-light';
        $labelClass = $theme === 'metronic9'
            ? 'text-sm text-muted-foreground tabular-nums min-w-[4.5rem] text-center'
            : 'text-muted small text-center';
        $rootClass = $theme === 'metronic9'
            ? 'flex items-center gap-2 me-auto'
            : 'd-flex align-items-center gap-2 me-auto';
    @endphp
    <div
        class="{{ $rootClass }}"
        data-modal-record-nav-root
        hidden
        aria-label="{{ __('ui.modal_record_nav') }}"
    >
        <button
            type="button"
            class="{{ $btnClass }}"
            data-modal-record-nav="prev"
            disabled
            aria-label="{{ __('ui.previous_record') }}"
        >{{ __('ui.previous_record') }}</button>
        <span class="{{ $labelClass }}" data-modal-record-nav-label>—</span>
        <button
            type="button"
            class="{{ $btnClass }}"
            data-modal-record-nav="next"
            disabled
            aria-label="{{ __('ui.next_record') }}"
        >{{ __('ui.next_record') }}</button>
    </div>
@endif
