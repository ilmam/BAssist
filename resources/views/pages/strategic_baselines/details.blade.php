@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
        $narrativeFields = ['current_state', 'future_state', 'change_strategy'];
    @endphp

    <div class="strategic-baseline-doc">
    <x-card :title="$modelName.' Details'">
        <x-slot:toolbar>
            @if (entity_can($model, 'update'))
                <x-button type="link" href="{{ model_route($model, 'edit', $dto->id) }}" icon="pencil" iconOnly="true" color="primary" activeColor="primary"></x-button>
            @endif
            @if (entity_can($model, 'delete') && empty($dto->is_system))
                <x-button type="link" href="{{ model_modal_path($model, 'delete', $dto->id) }}" icon="trash" iconOnly="true" color="danger" activeColor="warning" class="ms-1 js-open-modal" data-modal-url="{{ model_modal_path($model, 'delete', $dto->id) }}"></x-button>
            @endif
        </x-slot>
        <div class="strategic-baseline-doc__fields">
            @foreach ($fields as $name => $value)
                <div class="kt-form-item @if (in_array((string) $name, $narrativeFields, true)) strategic-baseline-doc__narrative @endif">
                    <label class="kt-form-label font-semibold text-foreground">{{ \App\Helpers\Ui::fieldLabel((string) $name) }}</label>
                    <span class="text-sm font-normal text-foreground @if (in_array((string) $name, $narrativeFields, true)) strategic-baseline-doc__prose @endif">{{ $value }}</span>
                </div>
            @endforeach
        </div>
        <x-slot:footer>
            <x-button type="link" href="{{ model_route($model, 'index') }}" color="light">Back to list</x-button>
        </x-slot:footer>
    </x-card>
    </div>
@endsection

@push('styles')
    <style>
        .strategic-baseline-doc .kt-form-item {
            margin-bottom: 1.75rem;
        }

        .strategic-baseline-doc .kt-form-item:last-child {
            margin-bottom: 0;
        }

        .strategic-baseline-doc__narrative {
            padding-top: 0.35rem;
        }

        .strategic-baseline-doc__prose {
            display: block;
            white-space: pre-wrap;
            line-height: 1.55;
            margin-top: 0.15rem;
        }
    </style>
@endpush
