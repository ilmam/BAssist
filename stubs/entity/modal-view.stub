@php
    $modelName = class_basename($model);
@endphp

<x-modal-content :title="$modelName.' Details'">
    <x-details-view
        model="{{ $modelName }}"
        :dto="$dto"
        :fields="$fields"
    />

    <x-slot:footer>
        <x-modal-dismiss text="Close" />
    </x-slot:footer>
</x-modal-content>
