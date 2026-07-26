{{--
    Custom fetch-driven modal host. Do not set data-kt-modal — KTUI auto-inits
    those and fights our open/close class toggling used by data-modal-url buttons.

    Sizes (via $size, data-modal-size on content, or data-modal-size on trigger):
      sm | md | lg | xl | full (default)  — centered dialogs
      end | sheet                          — right-side sheet

    Header switcher: Small (sm) / Medium (lg) / Large (full) / Side (end).
    Side mode clears the blurry backdrop by default and pushes page content left
    (body.modal-sheet-push) so the list stays fully visible beside the panel;
    the eye toggle can dim/clear the backdrop in any size.
--}}
@php
    $size = $size !== '' ? $size : 'full';
@endphp
<div class="kt-modal z-50" id="{{ $id }}" aria-hidden="true" data-modal-host data-modal-size="{{ $size }}">
    <div class="kt-modal-backdrop" data-kt-modal-dismiss="true"></div>
    <div class="kt-modal-content" data-modal-container></div>
</div>
