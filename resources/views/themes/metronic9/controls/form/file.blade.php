@include(ui_form_view('_vars'))

@php
    $fileClass = 'kt-input file:me-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary';
@endphp

@if ($horizontal)
    <div class="flex flex-col lg:flex-row lg:items-start gap-2.5">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5" for="{{ $name }}">{{ $labelText }}</label>
        <div class="lg:flex-1">
            {{ Form::file($name, array_merge(['class' => $fileClass], $attributes)) }}
        </div>
    </div>
@else
    <div class="kt-form-item">
        <label class="kt-form-label" for="{{ $name }}">{{ $labelText }}</label>
        {{ Form::file($name, array_merge(['class' => $fileClass], $attributes)) }}
    </div>
@endif
