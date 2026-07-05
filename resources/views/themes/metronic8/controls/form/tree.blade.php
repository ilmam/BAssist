@include(ui_form_view('_vars'))

@php
    $typeaheadClass = Ui::keyset($attributes, 'autocomplete') !== null ? 'typeahead' : '';
@endphp

@if ($horizontal)
    <div class="row mb-6 {{ $typeaheadClass }}">
        <label class="col-lg-4 col-form-label fw-semibold fs-6" for="{{ $name }}_text">{{ $labelText }}</label>
        <div class="col-lg-8 fv-row">
            {{ Form::text($name.'_text', $textValue, array_merge(['class' => 'form-control form-control-solid open-tree', 'readonly' => 'readonly'], $attributes)) }}
            {{ Form::hidden($name, $value) }}
        </div>
    </div>
@else
    <div class="mb-6 {{ $typeaheadClass }}">
        <label class="form-label fw-semibold fs-6" for="{{ $name }}_text">{{ $labelText }}</label>
        {{ Form::text($name.'_text', $textValue, array_merge(['class' => 'form-control form-control-solid open-tree', 'readonly' => 'readonly'], $attributes)) }}
        {{ Form::hidden($name, $value) }}
    </div>
@endif
