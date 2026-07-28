@include(ui_form_view('_vars'))

@php
    extract(ui_form_field_layout_vars((string) ($name ?? ''), $attributes ?? []), EXTR_SKIP);
    $typeaheadClass = Ui::keyset($attributes, 'autocomplete') !== null ? 'typeahead' : '';
@endphp

@if ($horizontal)
    <div class="{{ $fieldRowClass }} {{ $typeaheadClass }}">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5" for="{{ $name }}_text">{{ $labelText }}</label>
        <div class="lg:flex-1">
            {{ Form::text($name.'_text', $textValue, array_merge(['class' => 'kt-input open-tree', 'readonly' => 'readonly'], $attributes)) }}
            {{ Form::hidden($name, $value) }}
        </div>
    </div>
@else
    <div class="{{ $fieldStackClass }} {{ $typeaheadClass }}">
        <label class="kt-form-label" for="{{ $name }}_text">{{ $labelText }}</label>
        {{ Form::text($name.'_text', $textValue, array_merge(['class' => 'kt-input open-tree', 'readonly' => 'readonly'], $attributes)) }}
        {{ Form::hidden($name, $value) }}
    </div>
@endif
