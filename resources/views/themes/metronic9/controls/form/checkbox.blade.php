@include(ui_form_view('_vars'))

@php
    $list = $list ?? ['1' => ''];
    $fieldHelp = (string) ($attributes['data-field-help'] ?? $attributes['help'] ?? '');
    $inline = isset($attributes['inline']) && $attributes['inline'] == true;
    unset($attributes['data-field-help'], $attributes['help'], $attributes['inline']);
    $listClass = $inline ? 'flex flex-wrap gap-4' : 'flex flex-col gap-2';
@endphp

@if ($horizontal)
    <div class="flex flex-col lg:flex-row lg:items-start gap-2.5">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5">{{ $labelText }}</label>
        <div class="lg:flex-1 space-y-2">
            <div class="{{ $listClass }}">
                @foreach ($list as $id => $text)
                    <label class="kt-checkbox-group">
                        {{ Form::checkbox($name, $id, $value == $id || ($value === true && (string) $id === '1'), ['class' => 'kt-checkbox kt-checkbox-sm']) }}
                        <span class="kt-checkbox-label">{{ $text }}</span>
                    </label>
                @endforeach
            </div>
            @if ($fieldHelp !== '')
                <p class="field-help">{{ $fieldHelp }}</p>
            @endif
        </div>
    </div>
@else
    <div class="kt-form-item">
        <label class="kt-form-label">{{ $labelText }}</label>
        <div class="{{ $listClass }}">
            @foreach ($list as $id => $text)
                <label class="kt-checkbox-group">
                    {{ Form::checkbox($name, $id, $value == $id || ($value === true && (string) $id === '1'), ['class' => 'kt-checkbox kt-checkbox-sm']) }}
                    <span class="kt-checkbox-label">{{ $text }}</span>
                </label>
            @endforeach
        </div>
        @if ($fieldHelp !== '')
            <p class="field-help">{{ $fieldHelp }}</p>
        @endif
    </div>
@endif
