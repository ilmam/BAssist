@php
    $language = $language ?? 'gherkin';
    $readonly = $readonly ?? true;
    $showCopy = $showCopy ?? true;
    $downloadUrl = $downloadUrl ?? null;
    $editorId = $editorId ?? ('gherkin_'.uniqid());
@endphp

<div
    class="code-editor {{ $readonly ? 'code-editor--readonly' : '' }}"
    data-code-editor
    data-language="{{ $language }}"
    @if ($readonly) data-readonly="true" @endif
>
    <div class="code-editor__chrome">
        <span class="code-editor__lang">{{ $language }}</span>
        <div class="code-editor__actions">
            @if ($showCopy)
                <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-code-copy>
                    {{ __('ui.copy_gherkin') }}
                </button>
            @endif
            @if ($downloadUrl)
                <a href="{{ $downloadUrl }}" class="kt-btn kt-btn-sm kt-btn-outline">
                    {{ __('ui.download_feature') }}
                </a>
            @endif
        </div>
    </div>
    <textarea
        id="{{ $editorId }}_input"
        class="code-editor__input"
        data-code-input
        readonly
        aria-hidden="true"
        tabindex="-1"
    >{{ $source }}</textarea>
    <div id="{{ $editorId }}" class="code-editor__mount" data-code-mount data-language="{{ $language }}" tabindex="0"></div>
</div>
