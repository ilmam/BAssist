@include(ui_form_view('_vars'))

@php
    $list = $list ?? ['1' => ''];
    $inline = Ui::keyset($attributes, 'inline') !== null && $attributes['inline'] == true;
    $listClass = $inline ? 'flex flex-wrap gap-4' : 'flex flex-col gap-2';
@endphp

@if ($horizontal)
    <div class="flex flex-col lg:flex-row lg:items-start gap-2.5 mb-5">
        <label class="lg:w-1/4 text-sm font-medium text-foreground lg:pt-2.5">{{ $labelText }}</label>
        <div class="lg:flex-1 {{ $listClass }}">
            @foreach ($list as $id => $text)
                <label class="kt-checkbox-group">
                    {{ Form::checkbox($name, $id, $value == $id, ['class' => 'kt-checkbox kt-checkbox-sm']) }}
                    <span class="kt-checkbox-label">{{ $text }}</span>
                </label>
            @endforeach
        </div>
    </div>
@else
    <div class="flex flex-col gap-2 mb-5">
        <label class="text-sm font-medium text-foreground">{{ $labelText }}</label>
        <div class="{{ $listClass }}">
            @foreach ($list as $id => $text)
                <label class="kt-checkbox-group">
                    {{ Form::checkbox($name, $id, $value == $id, ['class' => 'kt-checkbox kt-checkbox-sm']) }}
                    <span class="kt-checkbox-label">{{ $text }}</span>
                </label>
            @endforeach
        </div>
    </div>
@endif
