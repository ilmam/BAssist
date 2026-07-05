@include(ui_form_view('_vars'))

@php
    $list = $list ?? [];
@endphp

@if ($horizontal)
    <div class="row mb-6">
        <label class="col-lg-4 col-form-label fw-semibold fs-6" for="{{ $name }}">{{ $labelText }}</label>
        <div class="col-lg-8 fv-row">
            {{ Form::select($name, $list, $value, array_merge(['class' => 'form-select form-select-solid'], $attributes)) }}
        </div>
    </div>
@else
    <div class="mb-6">
        <label class="form-label fw-semibold fs-6" for="{{ $name }}">{{ $labelText }}</label>
        {{ Form::select($name, $list, $value, array_merge(['class' => 'form-select form-select-solid'], $attributes)) }}
    </div>
@endif
