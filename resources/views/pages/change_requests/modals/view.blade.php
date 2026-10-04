@php
    $modelName = class_basename($model);
@endphp

<x-record-view :model="$model" :dto="$dto" :in-modal="true">
    <x-details-view
        model="{{ $modelName }}"
        :dto="$dto"
        :fields="$fields"
    />

    @include('pages.change_requests.partials.cascade', ['cascade' => $cascade ?? []])

    <x-slot:footer>
        @if (! empty($canApprove) && ! empty($approveUrl))
            <x-button
                type="link"
                href="{{ $approveUrl }}"
                color="primary"
                class="js-open-modal"
                data-modal-url="{{ $approveUrl }}"
            >{{ __('ui.change_request_approve_taint') }}</x-button>
        @endif
    </x-slot:footer>
</x-record-view>
