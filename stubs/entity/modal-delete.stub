@php
    $modelName = class_basename($model);
@endphp

<x-modal-content :title="'Delete '.$modelName">
    <p class="mb-5">Are you sure you want to delete this record?</p>

    <x-details-view
        model="{{ $modelName }}"
        :dto="$dto"
        :fields="$fields"
    />

    <x-slot:footer>
        <x-modal-dismiss text="Cancel" />
        <form method="POST" action="{{ model_route($model, 'destroy', $dto->id) }}" data-modal-form="true">
            @csrf
            @method('DELETE')
            <x-button type="submit" color="danger">Delete</x-button>
        </form>
    </x-slot:footer>
</x-modal-content>
