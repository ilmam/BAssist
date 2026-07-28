@include(ui_form_view('_vars'))

@php
    extract(ui_form_field_layout_vars((string) ($name ?? ''), $attributes ?? []), EXTR_SKIP);
    $fieldHelp = (string) ($attributes['data-field-help'] ?? $attributes['help'] ?? '');
    unset($attributes['data-field-help'], $attributes['help']);
@endphp

@if ($horizontal)
    <div class="{{ $fieldRowClass }}">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5" for="{{ $name }}">{{ $labelText }}</label>
        <div class="lg:flex-1 flex flex-col gap-2.5">
            {{ Form::textarea($name, $value, array_merge(['class' => 'kt-textarea', 'rows' => 6], $attributes)) }}
            @if ($fieldHelp !== '')
                <p class="kt-form-description">{{ $fieldHelp }}</p>
            @endif
        </div>
    </div>
@else
    <div class="{{ $fieldStackClass }}">
        <label class="kt-form-label" for="{{ $name }}">{{ $labelText }}</label>
        {{ Form::textarea($name, $value, array_merge(['class' => 'kt-textarea', 'rows' => 6], $attributes)) }}
        @if ($fieldHelp !== '')
            <p class="kt-form-description">{{ $fieldHelp }}</p>
        @endif
    </div>
@endif
