
@php
    use App\Facades\Form;
    $formRoute = in_array($verb, ['POST', 'post'], true)
        ? ['route' => $route]
        : ['route' => [$route, $dto->id]];
@endphp

{{ Form::open(array_merge($formRoute, ['id' => 'form1', 'files' => true, 'method' => 'post'])) }}
    <div class="{{ $inModal ? '' : 'kt-card-body border-t border-border p-5 lg:p-7.5' }}">
        @if (! in_array($verb, ['POST', 'post'], true))
            @method($verb)
        @endif

        @if ($dto->id ?? null)
            {{ Form::hidden('id', $dto->id) }}
        @endif

        @foreach ($fieldsArray as $name => $field)
            @php
                $fieldName = is_numeric($name) ? $field : $name;
                $type = \App\Helpers\FormHelper::getFieldType($field);

                $list = null;
                $options = null;

                if (isset($field['list'])) {
                    $list = $field['list'];
                }
            @endphp
            {{ Form::field($type, $fieldName, $dto->{$fieldName} ?? null, $list, $options) }}
        @endforeach
    </div>
    <div class="{{ $inModal ? 'flex justify-end gap-2.5 mt-5' : 'kt-card-footer flex justify-end gap-2.5 border-t border-border p-5 lg:p-7.5' }}">
        @if ($inModal)
            <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Cancel</button>
        @else
            <x-button type="link" href="{{ $cancelRoute }}" color="secondary">Cancel</x-button>
        @endif
        <x-button type="submit" color="primary">Save</x-button>
    </div>
{{ Form::close() }}
