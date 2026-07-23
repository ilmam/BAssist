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
                    $fieldValue = $dto->{$fieldName} ?? null;

                    $list = null;
                    $options = [];

                    if (isset($field['list'])) {
                        $list = $field['list'];
                    }

                    if (! empty($field['readonly'])) {
                        $options['readonly'] = 'readonly';
                        $options['disabled'] = 'disabled';
                        if (blank($fieldValue)) {
                            $options['placeholder'] = __('ui.code_assigned_on_save');
                        }
                    }

                    if ($quickCreate && $type === 'textarea') {
                        $options['rows'] = 2;
                    }

                    // Multi-stop spans via container queries (ui-layout.css).
                    // Defaults: sm:12 md:6 lg:4 — textarea/dropzone stay 12 at all stops.
                    $clamp = static fn (int $n): int => max(1, min(12, $n));
                    $isWide = in_array($type, ['textarea', 'dropzone'], true);
                    $defaults = $isWide
                        ? ['sm' => 12, 'md' => 12, 'lg' => 12]
                        : ['sm' => 12, 'md' => 6, 'lg' => 4];
                    $raw = $field['ui_span'] ?? null;
                    if (is_int($raw) || (is_string($raw) && ctype_digit($raw))) {
                        $n = $clamp((int) $raw);
                        $span = ['sm' => $n, 'md' => $n, 'lg' => $n];
                    } elseif (is_array($raw)) {
                        $span = $defaults;
                        foreach (['sm', 'md', 'lg'] as $k) {
                            if (isset($raw[$k])) {
                                $span[$k] = $clamp((int) $raw[$k]);
                            }
                        }
                    } else {
                        $span = $defaults;
                    }
                @endphp

                @if ($quickCreate)
                    <div
                        data-ui-span="{{ $span['sm'] }}"
                        data-ui-span-md="{{ $span['md'] }}"
                        data-ui-span-lg="{{ $span['lg'] }}"
                    >
                        {{ Form::field($type, $fieldName, $fieldValue, $list, $options) }}
                    </div>
                @else
                    {{ Form::field($type, $fieldName, $fieldValue, $list, $options ?: null) }}
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
