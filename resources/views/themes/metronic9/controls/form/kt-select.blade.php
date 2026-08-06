@include(ui_form_view('_vars'))

@php
    extract(ui_form_field_layout_vars((string) ($name ?? ''), $attributes ?? []), EXTR_SKIP);
    $list = $list ?? [];
    $fieldHelp = (string) ($attributes['data-field-help'] ?? $attributes['help'] ?? '');
    [$selectAttrs, $selectAttributes] = ui_form_select_attrs($attributes ?? [], forceKtSelect: true);
@endphp

@if ($horizontal)
    <div class="{{ $fieldRowClass }}">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5" for="{{ $name }}">{{ $labelText }}</label>
        <div class="lg:flex-1 flex flex-col gap-2.5">
            {{ Form::select($name, $list, $value, array_merge($selectAttrs, $selectAttributes)) }}
            @if ($fieldHelp !== '')
                <p class="kt-form-description">{{ $fieldHelp }}</p>
            @endif
        </div>
    </div>
@else
    <div class="{{ $fieldStackClass }}">
        <label class="kt-form-label" for="{{ $name }}">{{ $labelText }}</label>
        {{ Form::select($name, $list, $value, array_merge($selectAttrs, $selectAttributes)) }}
        @if ($fieldHelp !== '')
            <p class="kt-form-description">{{ $fieldHelp }}</p>
        @endif
    </div>
@endif
