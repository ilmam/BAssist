@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
        $critical = (bool) ($dto->is_critical ?? false);
        $gap = (bool) ($dto->has_coverage_gap ?? false);
    @endphp

    <x-card :title="$modelName.' Details'">
        <x-slot:toolbar>
            @if (entity_can($model, 'update'))
                <x-button type="link" href="{{ model_modal_path($model, 'edit', $dto->id) }}" icon="pencil" iconOnly="true" color="primary" activeColor="primary" class="js-open-modal" data-modal-url="{{ model_modal_path($model, 'edit', $dto->id) }}"></x-button>
            @endif
            @if (entity_can($model, 'delete') && empty($dto->is_system))
                <x-button type="link" href="{{ model_modal_path($model, 'delete', $dto->id) }}" icon="trash" iconOnly="true" color="danger" activeColor="warning" class="ms-1 js-open-modal" data-modal-url="{{ model_modal_path($model, 'delete', $dto->id) }}"></x-button>
            @endif
        </x-slot>

        @if ($critical || $gap)
            <div class="risk-alert risk-alert--{{ $gap ? 'gap' : 'critical' }} mb-5">
                @if ($critical)
                    <strong>{{ __('ui.risk_critical_highlight') }}:</strong>
                    {{ $dto->score_label }}
                @endif
                @if ($gap)
                    <div class="mt-1">{{ __('ui.risk_coverage_gap') }}</div>
                @endif
            </div>
        @endif

        <x-details-view
            model="{{ $modelName }}"
            :dto="$dto"
            :fields="$fields"
        />

        <x-slot:footer>
            <x-button type="link" href="{{ model_route($model, 'index') }}" color="outline">Back to list</x-button>
        </x-slot:footer>
    </x-card>
@endsection
