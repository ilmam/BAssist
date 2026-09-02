@include(ui_form_view('_vars'))

@php
    extract(ui_form_field_layout_vars((string) ($name ?? ''), $attributes ?? []), EXTR_SKIP);
    $fieldHelp = (string) ($attributes['data-field-help'] ?? $attributes['help'] ?? '');
    $attachableModel = $attributes['attachable_model'] ?? $attributes['model'] ?? '';
    $recordId = (int) ($attributes['record_id'] ?? 0);
    $readonly = ! empty($attributes['readonly']) || ! empty($attributes['disabled']);
    unset(
        $attributes['data-field-help'],
        $attributes['help'],
        $attributes['attachable_model'],
        $attributes['model'],
        $attributes['record_id'],
        $attributes['readonly'],
        $attributes['disabled'],
        $attributes['layout']
    );
    $fileClass = 'kt-input file:me-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary';
@endphp

@if ($horizontal)
    <div class="{{ $fieldRowClass }}">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5" for="{{ $name }}">{{ $labelText }}</label>
        <div class="lg:flex-1 flex flex-col gap-2.5">
            @include('pages.partials.attachments', [
                'model' => $attachableModel,
                'recordId' => $recordId,
                'fieldName' => $name,
                'mode' => 'field',
                'readonly' => $readonly,
                'fileInputClass' => $fileClass,
            ])
            @if ($fieldHelp !== '')
                <p class="kt-form-description">{{ $fieldHelp }}</p>
            @endif
        </div>
    </div>
@else
    <div class="{{ $fieldStackClass }}">
        <label class="kt-form-label" for="{{ $name }}">{{ $labelText }}</label>
        @include('pages.partials.attachments', [
            'model' => $attachableModel,
            'recordId' => $recordId,
            'fieldName' => $name,
            'mode' => 'field',
            'readonly' => $readonly,
            'fileInputClass' => $fileClass,
        ])
        @if ($fieldHelp !== '')
            <p class="kt-form-description">{{ $fieldHelp }}</p>
        @endif
    </div>
@endif
