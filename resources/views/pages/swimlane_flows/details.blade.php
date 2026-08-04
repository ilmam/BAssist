@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
    @endphp

    <x-card :title="$modelName.' Details'">
        <x-slot:toolbar>
            @if (entity_can($model, 'update'))
                <x-button type="link" href="{{ model_route($model, 'edit', $dto->id) }}" icon="pencil" iconOnly="true" color="primary" activeColor="primary"></x-button>
            @endif
            @if (entity_can($model, 'delete'))
                <x-button type="link" href="{{ model_modal_path($model, 'delete', $dto->id) }}" icon="trash" iconOnly="true" color="danger" activeColor="warning" class="ms-1 js-open-modal" data-modal-url="{{ model_modal_path($model, 'delete', $dto->id) }}"></x-button>
            @endif
        </x-slot>

        @include('pages.swimlane_flows.partials.view-content', [
            'dto' => $dto,
            'model' => $model,
            'fields' => $fields,
            'satisfyOptions' => $satisfyOptions ?? [],
        ])

        <x-slot:footer>
            <x-button type="link" href="{{ model_route($model, 'index') }}" color="light">Back to list</x-button>
            @if (entity_can($model, 'update'))
                <x-button type="link" href="{{ model_route($model, 'edit', $dto->id) }}" color="primary">Edit</x-button>
            @endif
        </x-slot:footer>
    </x-card>
@endsection
