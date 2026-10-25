@php
    $modelName = class_basename($model);
@endphp

<x-record-view :model="$model" :dto="$dto" :in-modal="true">
    @if (\App\Livewire\EntityView::supports($modelName))
        <livewire:entity-view :model="$modelName" :record-id="(int) $dto->id" :key="'entity-view-'.$modelName.'-'.$dto->id" />
    @else
        <x-details-view
            model="{{ $modelName }}"
            :dto="$dto"
            :fields="$fields"
        />
    @endif
</x-record-view>
