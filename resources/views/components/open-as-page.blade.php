{{-- Permalink: open the pop-up's content as a full page in a new tab. --}}
@props(['href'])
<a href="{{ $href }}" target="_blank" rel="noopener"
   {{ $attributes->class([ui_btn_classes('ghost', 'sm'), 'kt-btn-icon']) }}
   title="{{ __('ui.open_as_page') }}" aria-label="{{ __('ui.open_as_page') }}">
    <i class="ki-filled ki-exit-right-corner" aria-hidden="true"></i>
</a>
