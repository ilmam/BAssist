@php
    $list = $list ?? [];
    $attributes = $attributes ?? [];
@endphp

@switch($type)
    @case('text')
        {{ Form::bsText($name, $value, $attributes) }}
        @break

    @case('textarea')
        {{ Form::bsTextarea($name, $value, $attributes) }}
        @break

    @case('select')
        {{ Form::bsSelect($name, $value, $list, $attributes) }}
        @break

    @case('checkbox')
        {{ Form::bsCheckbox($name, $value, $list, $attributes) }}
        @break

    @case('radio')
        {{ Form::bsRadio($name, $value, $list, $attributes) }}
        @break

    @case('file')
        {{ Form::bsFile($name, $value, $attributes) }}
        @break

    @case('dropzone')
        {{ Form::bsDropzone($name, $value, $attributes) }}
        @break

    @case('image')
        {{ Form::bsImage($name, $value, $attributes) }}
        @break

    @case('hidden')
        {{ Form::hidden($name, $value) }}
        @break

    @default
        <span class="text-danger">Invalid form type [{{ $type }}]</span>
@endswitch
