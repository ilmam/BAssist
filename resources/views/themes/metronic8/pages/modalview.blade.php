@php
    $modelName = class_basename($model);
@endphp

<div class="modal-header">
    <h3 class="modal-title">{{ $modelName }} Details</h3>
    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close">
        <i class="fa fa-times"></i>
    </button>
</div>

<div class="modal-body">
    <x-details-view
        model="{{ $modelName }}"
        :dto="$dto"
        :fields="$fields"
    />
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
</div>
