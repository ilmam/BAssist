@include(ui_form_view('_vars'))

@php
    $typeaheadClass = Ui::keyset($attributes, 'autocomplete') !== null ? 'typeahead' : '';
@endphp

@if ($horizontal)
    <div class="flex flex-col lg:flex-row lg:items-start gap-2.5 {{ $typeaheadClass }}">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5" for="{{ $name }}_text">{{ $labelText }}</label>
        <div class="lg:flex-1">
            {{ Form::text($name.'_text', $textValue, array_merge(['class' => 'kt-input open-tree', 'readonly' => 'readonly'], $attributes)) }}
            {{ Form::hidden($name, $value) }}
        </div>
    </div>
@else
    <div class="kt-form-item {{ $typeaheadClass }}">
        <label class="kt-form-label" for="{{ $name }}_text">{{ $labelText }}</label>
        {{ Form::text($name.'_text', $textValue, array_merge(['class' => 'kt-input open-tree', 'readonly' => 'readonly'], $attributes)) }}
        {{ Form::hidden($name, $value) }}
    </div>
@endif
