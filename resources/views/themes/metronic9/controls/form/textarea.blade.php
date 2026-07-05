@include(ui_form_view('_vars'))

@if ($horizontal)
    <div class="flex flex-col lg:flex-row lg:items-start gap-2.5 mb-5">
        <label class="lg:w-1/4 text-sm font-medium text-foreground lg:pt-2.5" for="{{ $name }}">{{ $labelText }}</label>
        <div class="lg:flex-1">
            {{ Form::textarea($name, $value, array_merge(['class' => 'kt-input w-full', 'rows' => 3], $attributes)) }}
        </div>
    </div>
@else
    <div class="flex flex-col gap-1 mb-5">
        <label class="text-sm font-medium text-foreground" for="{{ $name }}">{{ $labelText }}</label>
        {{ Form::textarea($name, $value, array_merge(['class' => 'kt-input w-full', 'rows' => 3], $attributes)) }}
    </div>
@endif
