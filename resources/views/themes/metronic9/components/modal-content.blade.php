<div class="kt-modal-header">
    <h3 class="kt-modal-title">{{ $title }}</h3>
    <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true" type="button" aria-label="Close">
        <i class="ki-filled ki-cross"></i>
    </button>
</div>

<div class="kt-modal-body">
    {{ $slot }}
</div>

@isset($footer)
    <div class="kt-modal-footer flex justify-end gap-2.5">
        {{ $footer }}
    </div>
@endisset
