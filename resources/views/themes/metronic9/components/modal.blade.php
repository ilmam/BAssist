{{--
    Custom fetch-driven modal host. Do not set data-kt-modal — KTUI auto-inits
    those and fights our open/close class toggling used by data-modal-url buttons.

    Sizes (via $size, data-modal-size on content, or data-modal-size on trigger):
      sm | md | lg (default) | xl | full  — centered dialogs
      end | sheet              — right-side sheet
--}}
@php
    $size = $size !== '' ? $size : 'lg';
@endphp
<div class="kt-modal z-50" id="{{ $id }}" aria-hidden="true" data-modal-host data-modal-size="{{ $size }}">
    <div class="kt-modal-backdrop" data-kt-modal-dismiss="true"></div>
    <div class="kt-modal-content" data-modal-container></div>
</div>
