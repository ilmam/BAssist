@php
    use App\Facades\Form;
    $formRoute = in_array($verb, ['POST', 'post'], true)
        ? ['route' => $route]
        : ['route' => [$route, $dto->id]];
    $formOpenOptions = array_merge($formRoute, ['id' => $id, 'files' => true, 'method' => 'post']);

    if ($inModal) {
        $formOpenOptions['attributes'] = ['data-modal-form' => 'true'];
    }

    if ($quickCreate) {
        $formOpenOptions['attributes'] = array_merge(
            $formOpenOptions['attributes'] ?? [],
            [
                'data-modal-form' => 'true',
                'data-quick-create-form' => 'true',
            ]
        );
    }

    $fieldsWrapperClass = $quickCreate
        ? 'row g-3'
        : '';
@endphp

{{ Form::open($formOpenOptions) }}
    <div class="{{ $inModal ? '' : 'card-body border-top p-9' }}">
        <div class="{{ $fieldsWrapperClass }}">
            @if (! in_array($verb, ['POST', 'post'], true))
                @method($verb)
            @endif

            @if ($dto->id ?? null)
                {{ Form::hidden('id', $dto->id) }}
            @endif

            @if ($quickCreate)
                @foreach ($hiddenDefaults as $hiddenName => $hiddenValue)
                    {{ Form::hidden($hiddenName, $hiddenValue) }}
                @endforeach
            @endif

            @foreach ($fieldsArray as $name => $field)
                @php
                    $fieldName = is_numeric($name) ? $field : $name;
                    $type = \App\Helpers\FormHelper::getFieldType($field);

                    $list = null;
                    $options = [];

                    if (isset($field['list'])) {
                        $list = $field['list'];
                    }

                    if ($quickCreate && $type === 'textarea') {
                        $options['rows'] = 2;
                    }

                    // Explicit full class strings (Bootstrap 12-col grid).
                    $quickSpan = (int) ($field['quick_span'] ?? (
                        in_array($type, ['textarea', 'dropzone'], true) ? 12 : 4
                    ));
                    $colClass = match (max(1, min(12, $quickSpan))) {
                        1 => 'col-12 col-md-1',
                        2 => 'col-12 col-md-2',
                        3 => 'col-12 col-md-3',
                        4 => 'col-12 col-md-4',
                        5 => 'col-12 col-md-5',
                        6 => 'col-12 col-md-6',
                        7 => 'col-12 col-md-7',
                        8 => 'col-12 col-md-8',
                        9 => 'col-12 col-md-9',
                        10 => 'col-12 col-md-10',
                        11 => 'col-12 col-md-11',
                        default => 'col-12',
                    };
                @endphp

                @if ($quickCreate)
                    <div class="{{ $colClass }}">
                        {{ Form::field($type, $fieldName, $dto->{$fieldName} ?? null, $list, $options) }}
                    </div>
                @else
                    {{ Form::field($type, $fieldName, $dto->{$fieldName} ?? null, $list, $options ?: null) }}
                @endif
            @endforeach
        </div>
    </div>
    <div class="{{ $inModal ? 'd-flex justify-content-end gap-2 mt-4' : 'card-footer d-flex justify-content-end py-6 px-9' }}">
        @if ($quickCreate)
            <button type="button" class="btn btn-light d-none" data-qc-cancel-edit>{{ __('ui.cancel_edit') }}</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.done') }}</button>
            <button type="submit" class="btn btn-primary" data-qc-submit>{{ __('ui.add_another') }}</button>
        @elseif ($inModal)
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <x-button type="submit" class="btn btn-primary">Save</x-button>
        @else
            <x-button type="link" href="{{ $cancelRoute }}" class="btn btn-secondary me-2">Cancel</x-button>
            <x-button type="submit" class="btn btn-primary">Save</x-button>
        @endif
    </div>
{{ Form::close() }}
