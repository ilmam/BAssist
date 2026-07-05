@include(ui_form_view('_vars'))

@php
    $list = $list ?? [];
@endphp

@if ($horizontal)
    <div class="flex flex-col lg:flex-row lg:items-start gap-2.5 mb-5">
        <label class="lg:w-1/4 text-sm font-medium text-foreground lg:pt-2.5" for="{{ $name }}">{{ $labelText }}</label>
        <div class="lg:flex-1">
            {{ Form::select($name, $list, $value, array_merge(['class' => 'kt-select w-full', 'data-kt-select' => 'true'], $attributes)) }}
        </div>
    </div>
@else
    <div class="flex flex-col gap-1 mb-5">
        <label class="text-sm font-medium text-foreground" for="{{ $name }}">{{ $labelText }}</label>
        {{ Form::select($name, $list, $value, array_merge(['class' => 'kt-select w-full', 'data-kt-select' => 'true'], $attributes)) }}
    </div>
@endif
