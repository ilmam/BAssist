@include(ui_form_view('_vars'))

@php
    $list = $list ?? [];
    $fieldHelp = (string) ($attributes['data-field-help'] ?? $attributes['help'] ?? '');
    unset($attributes['data-field-help'], $attributes['help']);
@endphp

@if ($horizontal)
    <div class="flex flex-col lg:flex-row lg:items-start gap-2.5">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5" for="{{ $name }}">{{ $labelText }}</label>
        <div class="lg:flex-1">
            {{ Form::select($name, $list, $value, array_merge(['class' => 'kt-input'], $attributes)) }}
            @if ($fieldHelp !== '')
                <p class="field-help">{{ $fieldHelp }}</p>
            @endif
        </div>
    </div>
@else
    <div class="kt-form-item">
        <label class="kt-form-label" for="{{ $name }}">{{ $labelText }}</label>
        {{ Form::select($name, $list, $value, array_merge(['class' => 'kt-input'], $attributes)) }}
        @if ($fieldHelp !== '')
            <p class="field-help">{{ $fieldHelp }}</p>
        @endif
    </div>
@endif
