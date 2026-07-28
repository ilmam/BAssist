@extends(ui_layout())

@section('main')
    @php
        use App\Facades\Form;
        use App\Helpers\FormHelper;

        $modelName = class_basename($model);
        $verb = in_array($operation, ['insert', 'create'], true) ? 'POST' : 'PUT';
        $action = in_array($operation, ['insert', 'create'], true) ? 'store' : 'update';
        $title = ucfirst($operation).' '.$modelName;
        $route = model_route_name($model, $action);
        $cancelRoute = model_route_name($model, 'index');
        $formRoute = in_array($verb, ['POST', 'post'], true)
            ? ['route' => $route]
            : ['route' => [$route, $dto->id]];
        $transitions = is_array($dto->transitions ?? null) ? $dto->transitions : [];
    @endphp

    <x-form-card :title="$title">
        <x-slot:toolbar>
            <x-button type="link" href="{{ $cancelRoute }}" icon="arrow-left" iconOnly="true" color="light" activeColor="primary"></x-button>
        </x-slot>

        {{ Form::open(array_merge($formRoute, ['id' => 'form1', 'files' => true, 'method' => 'post'])) }}
            <div class="kt-card-body border-t border-border p-5 lg:p-7.5 space-y-8">
                @if (! in_array($verb, ['POST', 'post'], true))
                    @method($verb)
                @endif

                @if ($dto->id ?? null)
                    {{ Form::hidden('id', $dto->id) }}
                @endif

                <div class="form-fields">
                    @foreach ($formFields as $name => $field)
                        @php
                            $fieldName = is_numeric($name) ? $field : $name;
                            $type = FormHelper::getFieldType($field);
                            $fieldValue = $dto->{$fieldName} ?? null;
                            $list = $field['list'] ?? null;
                            $options = [];
                        @endphp
                        {{ Form::field($type, $fieldName, $fieldValue, $list, $options ?: null) }}
                    @endforeach
                </div>

                @include('pages.state_flows.partials.transitions-editor', [
                    'transitions' => $transitions,
                    'initialState' => $dto->initial_state,
                    'finalStates' => $dto->final_states,
                    'bodyOnly' => true,
                    'editable' => true,
                    'showTitleField' => false,
                ])
            </div>

            <div class="kt-card-footer flex justify-end gap-2.5 border-t border-border p-5 lg:p-7.5">
                <x-button type="link" href="{{ $cancelRoute }}" color="secondary">Cancel</x-button>
                <x-button type="submit" color="primary">Save</x-button>
            </div>
        {{ Form::close() }}
    </x-form-card>
@endsection
