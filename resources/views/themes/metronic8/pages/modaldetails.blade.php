@php
    $modelName = class_basename($model);
@endphp

<div class="modal-header">
    <h3 class="modal-title">Delete {{ $modelName }}</h3>
    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close">
        <i class="fa fa-times"></i>
    </button>
</div>

<div class="modal-body">
    <p class="mb-5">Are you sure you want to delete this record?</p>
    <x-details-view
        model="{{ $modelName }}"
        :dto="$dto"
        :fields="$fields"
    />
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
    <form method="POST" action="{{ model_route($model, 'destroy', $dto->id) }}" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
</div>
