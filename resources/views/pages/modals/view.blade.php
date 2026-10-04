@php
    $modelName = class_basename($model);
@endphp

<x-record-view :model="$model" :dto="$dto" :in-modal="true">
    <x-details-view
        model="{{ $modelName }}"
        :dto="$dto"
        :fields="$fields"
    />
</x-record-view>
