{{--
    Error slot under one form field. Filled by the server after a redirect back
    and by JavaScript (bassistShowFormErrors) after an AJAX / modal save.
    See docs/validation.md.
--}}
@php
    $errorBag = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $fieldError = $errorBag->first($fieldName) ?: $errorBag->first($fieldName.'.*');
    $fieldErrorClass = ui_theme() === 'metronic8' ? 'invalid-feedback d-block' : 'text-sm text-destructive mt-1.5';
@endphp
<div class="{{ $fieldErrorClass }}" data-field-error-for="{{ $fieldName }}" role="alert" @if ($fieldError === '') hidden @endif>{{ $fieldError }}</div>
