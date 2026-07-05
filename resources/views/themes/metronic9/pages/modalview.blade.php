@php
    $modelName = class_basename($model);
@endphp

<div class="kt-modal-header">
    <h3 class="kt-modal-title">{{ $modelName }} Details</h3>
    <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true" type="button">
        <i class="ki-filled ki-cross"></i>
    </button>
</div>

<div class="kt-modal-body">
    <x-details-view
        model="{{ $modelName }}"
        :dto="$dto"
        :fields="$fields"
    />
</div>

<div class="kt-modal-footer flex justify-end gap-2.5">
    <button class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true" type="button">Close</button>
</div>
