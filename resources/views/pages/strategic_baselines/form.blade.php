@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
        $verb = in_array($operation, ['insert', 'create'], true) ? 'POST' : 'PUT';
        $action = in_array($operation, ['insert', 'create'], true) ? 'store' : 'update';
        $title = ucfirst($operation).' '.$modelName;
        $route = model_route_name($model, $action);
        $cancelRoute = model_route_name($model, 'index');
    @endphp

    <div class="strategic-baseline-doc">
    <x-form-card :title="$title">
        <x-slot:toolbar>
            <x-button type="link" href="{{ $cancelRoute }}" icon="arrow-left" iconOnly="true" color="ghost" size="sm" activeColor="primary"></x-button>
        </x-slot>
        <x-form
            id="form1"
            route="{{ $route }}"
            verb="{{ $verb }}"
            model="{{ $modelName }}"
            :dto="$dto"
            :fieldsArray="$formFields"
        />
    </x-form-card>
    </div>
@endsection

@push('styles')
    <style>
        /* Slightly loftier stack for narrative baseline fields (scoped) */
        .strategic-baseline-doc .kt-form-item {
            margin-bottom: 1.75rem;
        }

        .strategic-baseline-doc .kt-form-item:last-child {
            margin-bottom: 0;
        }

        .strategic-baseline-doc textarea {
            min-height: 7.5rem;
        }
    </style>
@endpush
