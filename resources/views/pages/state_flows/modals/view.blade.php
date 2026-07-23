@php
    $modelName = class_basename($model);
@endphp

<x-modal-content :title="$modelName.' Details'" size="xl">
    @include('pages.state_flows.partials.view-content', [
        'dto' => $dto,
        'model' => $model,
        'fields' => $fields,
    ])

    <x-slot:footer>
        @if (entity_can($model, 'update'))
            <x-button
                type="link"
                href="{{ model_modal_path($model, 'edit', $dto->id) }}"
                color="primary"
                class="js-open-modal"
                data-modal-url="{{ model_modal_path($model, 'edit', $dto->id) }}"
            >Edit</x-button>
        @endif
        <x-modal-dismiss text="Close" />
    </x-slot:footer>
</x-modal-content>
