@if (config('ui.modal_record_nav', true))
    @php
        $theme = function_exists('ui_theme') ? ui_theme() : 'metronic9';
        $btnClass = $theme === 'metronic9'
            ? 'kt-btn kt-btn-sm kt-btn-icon kt-btn-outline'
            : 'btn btn-sm btn-icon btn-light';
        $labelClass = $theme === 'metronic9'
            ? 'text-sm text-muted-foreground tabular-nums min-w-[4.5rem] text-center'
            : 'text-muted small text-center';
        $rootClass = $theme === 'metronic9'
            ? 'flex items-center gap-2 me-auto'
            : 'd-flex align-items-center gap-2 me-auto';
        // Same icons as Metronic 9 list/pagination previous & next page controls.
        $prevIcon = $theme === 'metronic9'
            ? 'ki-outline ki-black-left'
            : 'fa fa-angle-left';
        $nextIcon = $theme === 'metronic9'
            ? 'ki-outline ki-black-right'
            : 'fa fa-angle-right';
        $prevLabel = __('ui.previous_record');
        $nextLabel = __('ui.next_record');
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
            aria-label="{{ $prevLabel }}"
            title="{{ $prevLabel }}"
        ><i class="{{ $prevIcon }}" aria-hidden="true"></i></button>
        <span class="{{ $labelClass }}" data-modal-record-nav-label>—</span>
        <button
            type="button"
            class="{{ $btnClass }}"
            data-modal-record-nav="next"
            disabled
            aria-label="{{ $nextLabel }}"
            title="{{ $nextLabel }}"
        ><i class="{{ $nextIcon }}" aria-hidden="true"></i></button>
    </div>
@endif
