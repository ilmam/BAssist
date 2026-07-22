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
        ? 'grid grid-cols-12 gap-x-4 gap-y-3'
        : 'space-y-6';
@endphp

{{ Form::open($formOpenOptions) }}
    <div class="{{ $inModal ? '' : 'kt-card-body border-t border-border p-5 lg:p-7.5' }}">
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

                    // Full class strings so Tailwind JIT/Purge keeps them.
                    $quickSpan = (int) ($field['quick_span'] ?? (
                        in_array($type, ['textarea', 'dropzone'], true) ? 12 : 4
                    ));
                    $fieldSpanClass = match (max(1, min(12, $quickSpan))) {
                        1 => 'col-span-12 sm:col-span-1',
                        2 => 'col-span-12 sm:col-span-2',
                        3 => 'col-span-12 sm:col-span-3',
                        4 => 'col-span-12 sm:col-span-4',
                        5 => 'col-span-12 sm:col-span-5',
                        6 => 'col-span-12 sm:col-span-6',
                        7 => 'col-span-12 sm:col-span-7',
                        8 => 'col-span-12 sm:col-span-8',
                        9 => 'col-span-12 sm:col-span-9',
                        10 => 'col-span-12 sm:col-span-10',
                        11 => 'col-span-12 sm:col-span-11',
                        default => 'col-span-12',
                    };
                @endphp

                @if ($quickCreate)
                    <div class="{{ $fieldSpanClass }}">
                        {{ Form::field($type, $fieldName, $dto->{$fieldName} ?? null, $list, $options) }}
                    </div>
                @else
                    {{ Form::field($type, $fieldName, $dto->{$fieldName} ?? null, $list, $options ?: null) }}
                @endif
            @endforeach
        </div>
    </div>
    <div class="{{ $inModal ? 'flex justify-end gap-2.5 mt-4' : 'kt-card-footer flex justify-end gap-2.5 border-t border-border p-5 lg:p-7.5' }}">
        @if ($quickCreate)
            <button type="button" class="kt-btn kt-btn-outline hidden" data-qc-cancel-edit>{{ __('ui.cancel_edit') }}</button>
            <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">{{ __('ui.done') }}</button>
            <button type="submit" class="kt-btn kt-btn-primary" data-qc-submit>{{ __('ui.add_another') }}</button>
        @elseif ($inModal)
            <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Cancel</button>
            <x-button type="submit" color="primary">Save</x-button>
        @else
            <x-button type="link" href="{{ $cancelRoute }}" color="secondary">Cancel</x-button>
            <x-button type="submit" color="primary">Save</x-button>
        @endif
    </div>
{{ Form::close() }}
