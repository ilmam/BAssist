@php
    use App\Facades\Form;
    use App\Helpers\FormHelper;

    $modelName = class_basename($model);
    $verb = in_array($operation, ['insert', 'create'], true) ? 'POST' : 'PUT';
    $action = in_array($operation, ['insert', 'create'], true) ? 'store' : 'update';
    $title = ucfirst($operation).' '.$modelName;
    $route = model_route_name($model, $action);
    $formRoute = in_array($verb, ['POST', 'post'], true)
        ? ['route' => $route]
        : ['route' => [$route, $dto->id]];
    $transitions = is_array($dto->transitions ?? null) ? $dto->transitions : [];
@endphp

<x-modal-content :title="$title" size="xl">
    {{ Form::open(array_merge($formRoute, [
        'id' => 'modalForm',
        'files' => true,
        'method' => 'post',
        'attributes' => [
            'data-modal-form' => 'true',
        ],
    ])) }}
        <div class="space-y-6">
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
                    @endphp
                    {{ Form::field($type, $fieldName, $fieldValue, $list, null) }}
                @endforeach
            </div>

            @include('pages.state_flows.partials.transitions-editor', [
                'transitions' => $transitions,
                'initialState' => $dto->initial_state,
                'finalStates' => $dto->final_states,
                'bodyOnly' => true,
                'editable' => true,
            ])
        </div>

        <div class="flex justify-end gap-2.5 mt-4">
            <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Cancel</button>
            <x-button type="submit" color="primary">Save</x-button>
        </div>
    {{ Form::close() }}
</x-modal-content>
