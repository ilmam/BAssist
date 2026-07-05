@php
    $modelName = class_basename($model);
@endphp

<div class="kt-modal-header">
    <h3 class="kt-modal-title">Delete {{ $modelName }}</h3>
    <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true" type="button">
        <i class="ki-filled ki-cross"></i>
    </button>
</div>

<div class="kt-modal-body">
    <p class="mb-5 text-secondary-foreground">Are you sure you want to delete this record?</p>
    <x-details-view
        model="{{ $modelName }}"
        :dto="$dto"
        :fields="$fields"
    />
</div>

<div class="kt-modal-footer flex justify-end gap-2.5">
    <button class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true" type="button">Cancel</button>
    <form method="POST" action="{{ model_route($model, 'destroy', $dto->id) }}" data-modal-form="true">
        @csrf
        @method('DELETE')
        <button type="submit" class="kt-btn kt-btn-destructive">Delete</button>
    </form>
</div>
