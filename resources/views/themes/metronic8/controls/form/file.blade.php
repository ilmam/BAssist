@include(ui_form_view('_vars'))

@php
    $placeholder = Ui::keyset($attributes, 'placeholder');
    $placeholderText = $placeholder !== null ? __('ui.'.$placeholder) : __('ui.choose_file');
@endphp

@if ($horizontal)
    <div class="row mb-6">
        <label class="col-lg-4 col-form-label fw-semibold fs-6" for="{{ $name }}">{{ $labelText }}</label>
        <div class="col-lg-8 fv-row">
            {{ Form::file($name, array_merge(['class' => 'form-control form-control-solid'], $attributes)) }}
        </div>
    </div>
@else
    <div class="mb-6">
        <label class="form-label fw-semibold fs-6" for="{{ $name }}">{{ $labelText }}</label>
        {{ Form::file($name, array_merge(['class' => 'form-control form-control-solid'], $attributes)) }}
    </div>
@endif
