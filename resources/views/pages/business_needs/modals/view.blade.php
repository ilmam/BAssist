@php
    $modelName = class_basename($model);
@endphp

<x-record-view :model="$model" :dto="$dto" :in-modal="true">
    <div class="space-y-6">
        @include('pages.partials.spine-cascade', ['cascade' => $cascade ?? null, 'part' => 'before', 'inModal' => true])
        <x-details-view
            model="{{ $modelName }}"
            :dto="$dto"
            :fields="$fields">
        @include('pages.partials.spine-cascade', ['cascade' => $cascade ?? null, 'part' => 'after', 'inModal' => true])
        </x-details-view>
    </div>
</x-record-view>
