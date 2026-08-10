@php
    $language = $language ?? 'gherkin';
    $readonly = $readonly ?? true;
    $showCopy = $showCopy ?? true;
    $downloadUrl = $downloadUrl ?? null;
    $editorId = $editorId ?? ('gherkin_'.uniqid());
@endphp

@include('pages.partials.code-document', [
    'language' => $language,
    'readonly' => $readonly,
    'showCopy' => $showCopy,
    'copyLabel' => __('ui.copy_gherkin'),
    'downloadUrl' => $downloadUrl,
    'downloadLabel' => __('ui.download_feature'),
    'editorId' => $editorId,
    'source' => $source ?? '',
])
