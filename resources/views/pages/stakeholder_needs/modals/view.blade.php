@php
    $modelName = class_basename($model);
@endphp

<x-modal-content :title="($dto->code ? $dto->code.' — ' : '').$dto->title">
    <div class="space-y-6">
        @include('pages.partials.spine-cascade', ['cascade' => $cascade ?? null, 'part' => 'before', 'inModal' => true])
        <x-details-view
            model="{{ $modelName }}"
            :dto="$dto"
            :fields="$fields"
        />
        @include('pages.partials.spine-cascade', ['cascade' => $cascade ?? null, 'part' => 'after', 'inModal' => true])
    </div>

    <x-slot:footer>
        @include('pages.partials.modal-record-nav')
        @include('pages.change_requests.partials.request-change-button', [
            'dto' => $dto,
            'stakeholderNeedId' => (int) $dto->id,
        ])
        <x-modal-dismiss text="Close" />
    </x-slot:footer>
</x-modal-content>
