@include(ui_form_view('_vars'))

@php
    extract(ui_form_field_layout_vars((string) ($name ?? ''), $attributes ?? []), EXTR_SKIP);
    $fileClass = 'kt-input file:me-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary';
@endphp

@if ($horizontal)
    <div class="{{ $fieldRowClass }}">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5" for="{{ $name }}">{{ $labelText }}</label>
        <div class="lg:flex-1">
            {{ Form::file($name, array_merge(['class' => $fileClass], $attributes)) }}
        </div>
    </div>
@else
    <div class="{{ $fieldStackClass }}">
        <label class="kt-form-label" for="{{ $name }}">{{ $labelText }}</label>
        {{ Form::file($name, array_merge(['class' => $fileClass], $attributes)) }}
    </div>
@endif
