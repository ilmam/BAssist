@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
        $editModalUrl = model_modal_path($model, 'edit', $dto->id);
        $addScenarioModalUrl = model_modal_path('Scenario', 'create').'?feature_id='.$dto->id;
    @endphp

    <x-card :title="($dto->code ? $dto->code.' — ' : '').$dto->title">
        <x-slot:toolbar>
            @if (entity_can($model, 'update'))
                <x-button
                    type="link"
                    href="{{ $editModalUrl }}"
                    icon="pencil"
                    iconOnly="true"
                    color="primary"
                    activeColor="primary"
                    class="js-open-modal"
                    data-modal-url="{{ $editModalUrl }}"
                ></x-button>
            @endif
            @if (entity_can($model, 'delete') && empty($dto->is_system))
                <x-button type="link" href="{{ model_modal_path($model, 'delete', $dto->id) }}" icon="trash" iconOnly="true" color="danger" activeColor="warning" class="ms-1 js-open-modal" data-modal-url="{{ model_modal_path($model, 'delete', $dto->id) }}"></x-button>
            @endif
        </x-slot>

        @if (session('status'))
            <div class="kt-alert kt-alert-success mb-5">{{ session('status') }}</div>
        @endif

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
            <x-button type="link" href="{{ model_route($model, 'index') }}" color="light">{{ __('ui.back_to_list') }}</x-button>
            @include('pages.change_requests.partials.request-change-button', ['dto' => $dto])
            @if (entity_can('Scenario', 'create'))
                <x-button
                    type="link"
                    href="{{ $addScenarioModalUrl }}"
                    color="primary"
                    class="js-open-modal"
                    data-modal-url="{{ $addScenarioModalUrl }}"
                >{{ __('ui.add_scenario') }}</x-button>
            @endif
            @if (entity_can($model, 'update') && ! empty($importUrl))
                <x-button type="link" href="{{ $importUrl }}" color="light">{{ __('ui.import_feature_file') }}</x-button>
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
        </x-slot:footer>
    </x-card>
@endsection
