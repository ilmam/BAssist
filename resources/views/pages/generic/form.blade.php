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
            <x-button type="link" href="{{ $cancelRoute }}" icon="arrow-left" iconOnly="true" color="ghost" size="sm" activeColor="primary"></x-button>
        </x-slot>
        @if (\App\Livewire\EntityForm::supports($formFields, $modelName))
            <livewire:entity-form
                :model="$modelName"
                :values="$dto->toArray()"
                :record-id="$dto->id ?? null"
                :key="'entity-form-'.$modelName.'-'.($dto->id ?? 'new')"
            />
        @else
            <x-form
                id="form1"
                route="{{ $route }}"
                verb="{{ $verb }}"
                model="{{ $modelName }}"
                :dto="$dto"
                :fieldsArray="$formFields"
            />
        @endif
    </x-form-card>
@endsection
