@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
    @endphp

    <x-card :title="($dto->code ? $dto->code.' — ' : '').$dto->title">
        <x-slot:toolbar>
            @if (entity_can($model, 'update'))
                <x-button type="link" href="{{ model_modal_path($model, 'edit', $dto->id) }}" icon="pencil" iconOnly="true" color="primary" activeColor="primary" class="js-open-modal" data-modal-url="{{ model_modal_path($model, 'edit', $dto->id) }}"></x-button>
            @endif
            @if (entity_can($model, 'delete') && empty($dto->is_system))
                <x-button type="link" href="{{ model_modal_path($model, 'delete', $dto->id) }}" icon="trash" iconOnly="true" color="danger" activeColor="warning" class="ms-1 js-open-modal" data-modal-url="{{ model_modal_path($model, 'delete', $dto->id) }}"></x-button>
            @endif
        </x-slot>
        <x-details-view
            model="{{ $modelName }}"
            :dto="$dto"
            :fields="$fields"
        />
        <x-slot:footer>
            <x-button type="link" href="{{ model_route($model, 'index') }}" color="light">{{ __('ui.back_to_list') }}</x-button>
            @include('pages.change_requests.partials.request-change-button', [
                'dto' => $dto,
                'affectedType' => \App\Support\ChangeRequestAffectedType::FUNCTIONAL_REQUIREMENT,
            ])
        </x-slot:footer>
    </x-card>
@endsection
