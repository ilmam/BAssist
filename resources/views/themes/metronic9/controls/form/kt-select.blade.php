@include(ui_form_view('_vars'))

@php
    $list = $list ?? [];
@endphp

@if ($horizontal)
    <div class="flex flex-col lg:flex-row lg:items-start gap-2.5">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5" for="{{ $name }}">{{ $labelText }}</label>
        <div class="lg:flex-1">
            {{ Form::select($name, $list, $value, array_merge(['class' => 'kt-select', 'data-kt-select' => 'true'], $attributes)) }}
        </div>
    </div>
@else
    <div class="kt-form-item">
        <label class="kt-form-label" for="{{ $name }}">{{ $labelText }}</label>
        {{ Form::select($name, $list, $value, array_merge(['class' => 'kt-select', 'data-kt-select' => 'true'], $attributes)) }}
    </div>
@endif
