@php
    $modelName = class_basename($model);
    $editModalUrl = model_modal_path($model, 'edit', $dto->id);
    $addScenarioModalUrl = model_modal_path('Scenario', 'create').'?feature_id='.$dto->id;
@endphp

<x-modal-content :title="($dto->code ? $dto->code.' — ' : '').$dto->title" size="xl">
    @include('pages.features.partials.view-content', [
        'dto' => $dto,
        'model' => $model,
        'fields' => $fields,
        'feature' => $feature,
        'assembledGherkin' => $assembledGherkin ?? '',
        'tagList' => $tagList ?? [],
        'exportUrl' => $exportUrl ?? null,
        'printUrl' => $printUrl ?? null,
        'importUrl' => $importUrl ?? null,
    ])

    <x-slot:footer>
        @if (entity_can('Scenario', 'create'))
            <x-button
                type="link"
                href="{{ $addScenarioModalUrl }}"
                color="primary"
                class="js-open-modal"
                data-modal-url="{{ $addScenarioModalUrl }}"
            >{{ __('ui.add_scenario') }}</x-button>
        @endif
        @if (entity_can($model, 'update'))
            <x-button
                type="link"
                href="{{ $editModalUrl }}"
                color="primary"
                class="js-open-modal"
                data-modal-url="{{ $editModalUrl }}"
            >{{ __('ui.edit') }}</x-button>
        @endif
        <x-modal-dismiss text="Close" />
    </x-slot:footer>
</x-modal-content>
