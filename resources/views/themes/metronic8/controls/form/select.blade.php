@include(ui_form_view('_vars'))

@php
    $list = $list ?? [];
    $fieldHelp = (string) ($attributes['data-field-help'] ?? $attributes['help'] ?? '');
    unset($attributes['data-field-help'], $attributes['help']);
@endphp

@if ($horizontal)
    <div class="row mb-6">
        <label class="col-lg-4 col-form-label fw-semibold fs-6" for="{{ $name }}">{{ $labelText }}</label>
        <div class="col-lg-8 fv-row">
            {{ Form::select($name, $list, $value, array_merge(['class' => 'form-select form-select-solid'], $attributes)) }}
            @if ($fieldHelp !== '')
                <p class="field-help text-muted fs-7 mt-1 mb-0">{{ $fieldHelp }}</p>
            @endif
        </div>
    </div>
@else
    <div class="mb-6">
        <label class="form-label fw-semibold fs-6" for="{{ $name }}">{{ $labelText }}</label>
        {{ Form::select($name, $list, $value, array_merge(['class' => 'form-select form-select-solid'], $attributes)) }}
        @if ($fieldHelp !== '')
            <p class="field-help text-muted fs-7 mt-1 mb-0">{{ $fieldHelp }}</p>
        @endif
    </div>
@endif
