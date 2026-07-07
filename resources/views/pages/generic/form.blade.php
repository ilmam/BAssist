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

    <x-form-card :title="$title">
        <x-slot:toolbar>
            <x-button type="link" href="{{ $cancelRoute }}" icon="arrow-left" iconOnly="true" color="light" activeColor="primary"></x-button>
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
@endsection
