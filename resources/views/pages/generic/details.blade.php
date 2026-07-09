@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
    @endphp

    <x-card :title="$modelName.' Details'">
        <x-slot:toolbar>
            @if (entity_can($model, 'update'))
                <x-button type="link" href="{{ model_modal_path($model, 'edit', $dto->id) }}" icon="pencil" iconOnly="true" color="primary" activeColor="primary" class="js-open-modal" data-modal-url="{{ model_modal_path($model, 'edit', $dto->id) }}"></x-button>
            @endif
            @if (entity_can($model, 'delete'))
                <x-button type="link" href="{{ model_modal_path($model, 'delete', $dto->id) }}" icon="trash" iconOnly="true" color="danger" activeColor="warning" class="ms-1 js-open-modal" data-modal-url="{{ model_modal_path($model, 'delete', $dto->id) }}"></x-button>
            @endif
        </x-slot>
        <x-details-view
            model="{{ $modelName }}"
            :dto="$dto"
            :fields="$fields"
        />
        <x-slot:footer>
            <x-button type="link" href="{{ model_route($model, 'index') }}" color="light">Back to list</x-button>
        </x-slot:footer>
    </x-card>
@endsection
