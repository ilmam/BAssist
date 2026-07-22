{{--
    Custom fetch-driven modal host. Do not set data-kt-modal — KTUI auto-inits
    those and fights our open/close class toggling used by data-modal-url buttons.

    Use only utilities present in the prebuilt Metronic CSS (no arbitrary values).
    z-50 keeps the overlay above the fixed header (z-10) and sidebar (z-20).
--}}
<div class="kt-modal z-50" id="{{ $id }}" aria-hidden="true">
    <div class="kt-modal-backdrop" data-kt-modal-dismiss="true"></div>
    <div class="kt-modal-content top-[15%]" style="max-width: 720px;" data-modal-container></div>
</div>
