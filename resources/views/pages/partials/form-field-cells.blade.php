@php
    use App\Facades\Form;
    use App\Helpers\FormHelper;

    $fields = $fields ?? [];
    $dto = $dto ?? null;
    $quickCreate = (bool) ($quickCreate ?? false);
@endphp

@foreach ($fields as $name => $field)
    @php
        $fieldName = is_numeric($name) ? $field : $name;
        // Sticky project context — never render as a visible control.
        if ($fieldName === 'project_id') {
            continue;
        }

        $type = FormHelper::getFieldType($field);
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

        if ($type === 'code' && ! empty($field['language'])) {
            $options['data-language'] = $field['language'];
        }

        if (! empty($field['help'])) {
            $options['data-field-help'] = $field['help'];
        }

        if (array_key_exists('kt_select', $field)) {
            $options['kt_select'] = (bool) $field['kt_select'];
        }

        if ($type === 'radio') {
            $options['inline'] = true;
        }

        // Multi-stop spans via container queries (ui-layout.css).
        // Defaults: sm:12 md:6 lg:6 (half width) — textarea/code/dropzone stay 12 at all stops.
        $clamp = static fn (int $n): int => max(1, min(12, $n));
        $isWide = in_array($type, ['textarea', 'code', 'dropzone'], true);
        $defaults = $isWide
            ? ['sm' => 12, 'md' => 12, 'lg' => 12]
            : ['sm' => 12, 'md' => 6, 'lg' => 6];
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

    <div
        data-ui-span="{{ $span['sm'] }}"
        data-ui-span-md="{{ $span['md'] }}"
        data-ui-span-lg="{{ $span['lg'] }}"
    >
        {{ Form::field($type, $fieldName, $fieldValue, $list, $options ?: null) }}
    </div>
@endforeach
