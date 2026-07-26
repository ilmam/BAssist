@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
        $editModalUrl = model_modal_path($model, 'edit', $dto->id);
        $featureShowUrl = ! empty($scenario->feature_id)
            ? model_route('Feature', 'show', $scenario->feature_id)
            : model_route($model, 'index');
    @endphp

    <x-card :title="$scenario->gherkinKeyword().': '.$dto->title">
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

        @include('pages.scenarios.partials.view-content', [
            'dto' => $dto,
            'model' => $model,
            'fields' => $fields,
            'scenario' => $scenario,
            'gherkin' => $gherkin,
            'tagList' => $tagList ?? [],
        ])

        <x-slot:footer>
            <x-button type="link" href="{{ $featureShowUrl }}" color="light">
                {{ ! empty($scenario->feature_id) ? __('ui.back_to_feature') : __('ui.back_to_list') }}
            </x-button>
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
