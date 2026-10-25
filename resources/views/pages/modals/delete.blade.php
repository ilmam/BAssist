@php
    $modelName = class_basename($model);
@endphp

<x-modal-content :title="'Delete '.$modelName" size="sm">
    @if (\App\Livewire\EntityDelete::supports($modelName))
        <livewire:entity-delete :model="$modelName" :record-id="(int) $dto->id" :key="'entity-delete-'.$modelName.'-'.$dto->id" />
    @else
        <div class="space-y-6">
            <p class="text-sm text-secondary-foreground">Are you sure you want to delete this record?</p>

            <x-details-view
                model="{{ $modelName }}"
                :dto="$dto"
                :fields="$fields"
            />
        </div>

        <x-slot:footer>
            <x-modal-dismiss text="Cancel" />
            <form method="POST" action="{{ model_route($model, 'destroy', $dto->id) }}" data-modal-form="true">
                @csrf
                @method('DELETE')
                <x-button type="submit" color="danger">Delete</x-button>
            </form>
        </x-slot:footer>
    @endif
</x-modal-content>
