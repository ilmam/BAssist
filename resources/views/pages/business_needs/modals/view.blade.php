@php
    $modelName = class_basename($model);
@endphp

<x-modal-content :title="($dto->code ? $dto->code.' — ' : '').$dto->title">
    <div class="space-y-6">
        <x-details-view
            model="{{ $modelName }}"
            :dto="$dto"
            :fields="$fields"
        />
    </div>

    <x-slot:footer>
        <x-modal-dismiss text="Close" />
    </x-slot:footer>
</x-modal-content>
