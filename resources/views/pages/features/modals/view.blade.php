@php
    $modelName = class_basename($model);
    $addScenarioModalUrl = model_modal_path('Scenario', 'create').'?feature_id='.$dto->id;
@endphp

<x-record-view :model="$model" :dto="$dto" :in-modal="true" size="full">
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
        'cascade' => $cascade ?? null,
        'inModal' => true,
    ])

    <x-slot:footer>
        @if (entity_can($model, 'update') && ! empty($importUrl))
            <x-button type="link" href="{{ $importUrl }}" color="light">{{ __('ui.import_feature_file') }}</x-button>
        @endif
        @if (entity_can('Scenario', 'create'))
            <x-button
                type="link"
                href="{{ $addScenarioModalUrl }}"
                color="primary"
                class="js-open-modal"
                data-modal-url="{{ $addScenarioModalUrl }}"
            >{{ __('ui.add_scenario') }}</x-button>
        @endif
    </x-slot:footer>
</x-record-view>
