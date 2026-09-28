{{--
    Form-level error summary: messages that do not belong to a visible field
    (editor rows such as elements.3.lane, or a field not on this form). Field
    errors are shown under each field by form-field-error. Filled by the server
    after a redirect back and by JavaScript after an AJAX save.
    See docs/validation.md.
--}}
@php
    $errorBag = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $shownFields = array_map(
        fn ($key, $value) => is_numeric($key) ? (string) $value : (string) $key,
        array_keys($fieldNames ?? []),
        array_values($fieldNames ?? [])
    );
    $unplaced = [];
    foreach ($errorBag->getMessages() as $key => $messages) {
        $base = explode('.', (string) $key)[0];
        if (! in_array($base, $shownFields, true) || str_contains((string) $key, '.')) {
            array_push($unplaced, ...$messages);
        }
    }
    $summaryClass = ui_theme() === 'metronic8'
        ? 'alert alert-danger mb-5'
        : 'kt-alert kt-alert-destructive mb-5';
@endphp
<div class="{{ $summaryClass }}" data-form-errors role="alert" @if ($unplaced === []) hidden @endif>
    <ul class="list-disc ps-5 mb-0" data-form-errors-list>
        @foreach (array_unique($unplaced) as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
</div>
