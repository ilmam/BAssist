@php
    $language = $language ?? 'plaintext';
    $readonly = $readonly ?? true;
    $showCopy = $showCopy ?? true;
    $copyLabel = $copyLabel ?? __('ui.copy_gherkin');
    $downloadUrl = $downloadUrl ?? null;
    $downloadLabel = $downloadLabel ?? __('ui.download_feature');
    $editorId = $editorId ?? ('code_doc_'.uniqid());
    $compact = (bool) ($compact ?? false);
    $source = $source ?? '';
    $langLabel = $language === 'plaintext' ? 'Plain text' : $language;
@endphp

<div
    class="code-editor {{ $readonly ? 'code-editor--readonly' : '' }}{{ $compact ? ' code-editor--compact' : '' }}"
    data-code-editor
    data-language="{{ $language }}"
    @if ($readonly) data-readonly="true" @endif
>
    <div class="code-editor__chrome">
        <span class="code-editor__lang">{{ $langLabel }}</span>
        <div class="code-editor__actions">
            @if ($showCopy)
                <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-code-copy>
                    {{ $copyLabel }}
                </button>
            @endif
            @if ($downloadUrl)
                <a href="{{ $downloadUrl }}" class="kt-btn kt-btn-sm kt-btn-outline">
                    {{ $downloadLabel }}
                </a>
            @endif
        </div>
    </div>
    <textarea
        id="{{ $editorId }}_input"
        class="code-editor__input"
        data-code-input
        @if ($readonly) readonly @endif
        aria-hidden="true"
        tabindex="-1"
    >{{ $source }}</textarea>
    <div id="{{ $editorId }}" class="code-editor__mount" data-code-mount data-language="{{ $language }}" tabindex="0"></div>
</div>
