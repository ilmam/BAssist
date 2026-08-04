@php
    $modelName = class_basename($model);
@endphp

<x-modal-content :title="$modelName.' Details'">
    <x-details-view
        model="{{ $modelName }}"
        :dto="$dto"
        :fields="$fields"
    />

    @include('pages.change_requests.partials.cascade', ['cascade' => $cascade ?? []])
</x-modal-content>
