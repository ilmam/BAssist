@php
    $modelName = class_basename($model);
@endphp

<x-modal-content :title="$scenario->gherkinKeyword().': '.$dto->title" size="xl">
    @include('pages.scenarios.partials.view-content', [
        'dto' => $dto,
        'model' => $model,
        'fields' => $fields,
        'scenario' => $scenario,
        'gherkin' => $gherkin,
        'tagList' => $tagList ?? [],
    ])

    <x-slot:footer>
        @if (entity_can($model, 'update'))
            <x-button
                type="link"
                href="{{ model_modal_path($model, 'edit', $dto->id) }}"
                color="primary"
                class="js-open-modal"
                data-modal-url="{{ model_modal_path($model, 'edit', $dto->id) }}"
            >{{ __('ui.edit') }}</x-button>
        @endif
        <x-modal-dismiss text="Close" />
    </x-slot:footer>
</x-modal-content>
