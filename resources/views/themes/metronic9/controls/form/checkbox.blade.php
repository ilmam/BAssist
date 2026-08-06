@include(ui_form_view('_vars'))

@php
    extract(ui_form_field_layout_vars((string) ($name ?? ''), $attributes ?? []), EXTR_SKIP);
    $list = $list ?? ['1' => ''];
    $fieldHelp = (string) ($attributes['data-field-help'] ?? $attributes['help'] ?? '');
    $inline = isset($attributes['inline']) && $attributes['inline'] == true;
    unset($attributes['data-field-help'], $attributes['help'], $attributes['inline'], $attributes['layout']);
    $listClass = $inline ? 'flex flex-wrap gap-4' : 'flex flex-col gap-2';
@endphp

@if ($horizontal)
    <div class="{{ $fieldRowClass }}">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5">{{ $labelText }}</label>
        <div class="lg:flex-1 flex flex-col gap-2.5">
            <div class="{{ $listClass }}">
                @foreach ($list as $id => $text)
                    <label class="kt-label">
                        {{ Form::checkbox($name, $id, $value == $id || ($value === true && (string) $id === '1'), ['class' => 'kt-checkbox kt-checkbox-sm']) }}
                        {{ $text }}
                    </label>
                @endforeach
            </div>
            @if ($fieldHelp !== '')
                <p class="kt-form-description">{{ $fieldHelp }}</p>
            @endif
        </div>
    </div>
@else
    <div class="{{ $fieldStackClass }}">
        <label class="kt-form-label">{{ $labelText }}</label>
        <div class="{{ $listClass }}">
            @foreach ($list as $id => $text)
                <label class="kt-label">
                    {{ Form::checkbox($name, $id, $value == $id || ($value === true && (string) $id === '1'), ['class' => 'kt-checkbox kt-checkbox-sm']) }}
                    {{ $text }}
                </label>
            @endforeach
        </div>
        @if ($fieldHelp !== '')
            <p class="kt-form-description">{{ $fieldHelp }}</p>
        @endif
    </div>
@endif
