@include(ui_form_view('_vars'))

@php
    $language = strtolower((string) ($attributes['data-language'] ?? $attributes['language'] ?? 'plaintext'));
    $fieldHelp = (string) ($attributes['data-field-help'] ?? $attributes['help'] ?? '');
    unset($attributes['language'], $attributes['data-language'], $attributes['data-field-help'], $attributes['help']);

    $editorId = 'code_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $name);
    $extraClass = trim((string) ($attributes['class'] ?? ''));
    unset($attributes['class']);

    $textareaAttrs = array_merge(
        [
            'class' => trim('code-editor__input '.$extraClass),
            'rows' => 12,
            'data-code-input' => 'true',
            'aria-hidden' => 'true',
            'tabindex' => '-1',
        ],
        $attributes
    );

    $langLabel = $language === 'plaintext' ? 'Plain text' : $language;

    if ($fieldHelp === '' && $language === 'gherkin') {
        $fieldHelp = __('ui.gherkin_editor_help');
    }
@endphp

@if ($horizontal)
    <div class="flex flex-col lg:flex-row lg:items-start gap-2.5">
        <label class="kt-form-label lg:w-1/4 lg:pt-2.5" for="{{ $editorId }}">{{ $labelText }}</label>
        <div class="lg:flex-1">
            <div class="code-editor" data-code-editor data-language="{{ $language }}">
                <div class="code-editor__chrome">
                    <span class="code-editor__lang">{{ $langLabel }}</span>
                    <div class="code-editor__actions"></div>
                </div>
                {{ Form::textarea($name, $value, $textareaAttrs) }}
                <div id="{{ $editorId }}" class="code-editor__mount" data-code-mount data-language="{{ $language }}" tabindex="0"></div>
            </div>
            @if ($fieldHelp !== '')
                <p class="field-help">{{ $fieldHelp }}</p>
            @endif
        </div>
    </div>
@else
    <div class="kt-form-item">
        <label class="kt-form-label" for="{{ $editorId }}">{{ $labelText }}</label>
        <div class="code-editor" data-code-editor data-language="{{ $language }}">
            <div class="code-editor__chrome">
                <span class="code-editor__lang">{{ $langLabel }}</span>
                <div class="code-editor__actions"></div>
            </div>
            {{ Form::textarea($name, $value, $textareaAttrs) }}
            <div id="{{ $editorId }}" class="code-editor__mount" data-code-mount data-language="{{ $language }}" tabindex="0"></div>
        </div>
        @if ($fieldHelp !== '')
            <p class="field-help">{{ $fieldHelp }}</p>
        @endif
    </div>
@endif
