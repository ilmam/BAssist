@include(ui_form_view('_vars'))

@php
    $list = $list ?? [];
    $inline = Ui::keyset($attributes, 'inline') !== null && $attributes['inline'] == true;
    $listClass = $inline ? 'flex flex-wrap gap-4' : 'flex flex-col gap-2';
@endphp

@if ($horizontal)
    <div class="flex flex-col lg:flex-row lg:items-start gap-2.5">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5">{{ $labelText }}</label>
        <div class="lg:flex-1 {{ $listClass }}">
            @foreach ($list as $id => $text)
                <label class="flex items-center gap-2">
                    {{ Form::radio($name, $id, $value == $id, ['class' => 'kt-radio']) }}
                    <span class="text-sm text-foreground">{{ $text }}</span>
                </label>
            @endforeach
        </div>
    </div>
@else
    <div class="kt-form-item">
        <label class="kt-form-label">{{ $labelText }}</label>
        <div class="{{ $listClass }}">
            @foreach ($list as $id => $text)
                <label class="flex items-center gap-2">
                    {{ Form::radio($name, $id, $value == $id, ['class' => 'kt-radio']) }}
                    <span class="text-sm text-foreground">{{ $text }}</span>
                </label>
            @endforeach
        </div>
    </div>
@endif
