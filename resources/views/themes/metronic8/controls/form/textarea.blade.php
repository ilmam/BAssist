@include(ui_form_view('_vars'))

@if ($horizontal)
    <div class="row mb-6">
        <label class="col-lg-4 col-form-label fw-semibold fs-6" for="{{ $name }}">{{ $labelText }}</label>
        <div class="col-lg-8 fv-row">
            {{ Form::textarea($name, $value, array_merge(['class' => 'form-control form-control-solid', 'rows' => 3], $attributes)) }}
        </div>
    </div>
@else
    <div class="mb-6">
        <label class="form-label fw-semibold fs-6" for="{{ $name }}">{{ $labelText }}</label>
        {{ Form::textarea($name, $value, array_merge(['class' => 'form-control form-control-solid', 'rows' => 3], $attributes)) }}
    </div>
@endif
