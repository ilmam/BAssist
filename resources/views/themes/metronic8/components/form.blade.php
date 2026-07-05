
@php
    use App\Facades\Form;
    $formRoute = in_array($verb, ['POST', 'post'], true)
        ? ['route' => $route]
        : ['route' => [$route, $dto->id]];
@endphp

{{ Form::open(array_merge($formRoute, ['id' => 'form1', 'files' => true, 'method' => 'post'])) }}
    <div class="{{ $inModal ? '' : 'card-body border-top p-9' }}">
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
    <div class="{{ $inModal ? 'd-flex justify-content-end gap-2 mt-5' : 'card-footer d-flex justify-content-end py-6 px-9' }}">
        @if ($inModal)
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        @else
            <x-button type="link" href="{{ $cancelRoute }}" class="btn btn-secondary me-2">Cancel</x-button>
        @endif
        <x-button type="submit" class="btn btn-primary">Save</x-button>
    </div>
{{ Form::close() }}
