@include(ui_form_view('_vars'))

@php
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
    $fileClass = 'form-control form-control-solid';
@endphp

@if ($horizontal)
    <div class="row mb-6">
        <label class="col-lg-4 col-form-label fw-semibold fs-6" for="{{ $name }}">{{ $labelText }}</label>
        <div class="col-lg-8 fv-row">
            @include('pages.partials.attachments', [
                'model' => $attachableModel,
                'recordId' => $recordId,
                'fieldName' => $name,
                'mode' => 'field',
                'readonly' => $readonly,
                'fileInputClass' => $fileClass,
            ])
            @if ($fieldHelp !== '')
                <p class="form-text text-muted">{{ $fieldHelp }}</p>
            @endif
        </div>
    </div>
@else
    <div class="mb-6">
        <label class="form-label fw-semibold fs-6" for="{{ $name }}">{{ $labelText }}</label>
        @include('pages.partials.attachments', [
            'model' => $attachableModel,
            'recordId' => $recordId,
            'fieldName' => $name,
            'mode' => 'field',
            'readonly' => $readonly,
            'fileInputClass' => $fileClass,
        ])
        @if ($fieldHelp !== '')
            <p class="form-text text-muted">{{ $fieldHelp }}</p>
        @endif
    </div>
@endif
