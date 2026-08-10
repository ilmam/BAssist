@php
    $source = $source ?? '';
    $editorId = $editorId ?? ('mermaid_source_'.uniqid());
    $hidden = (bool) ($hidden ?? false);
    $wrapInDetails = (bool) ($wrapInDetails ?? true);
    $summary = $summary ?? __('ui.mermaid_source');
@endphp

@if ($wrapInDetails)
    <details class="mt-3">
        <summary class="text-xs text-muted-foreground cursor-pointer">{{ $summary }}</summary>
        <div class="mt-2" data-mermaid-source>
            @include('pages.partials.code-document', [
                'source' => $source,
                'language' => 'mermaid',
                'readonly' => true,
                'showCopy' => true,
                'editorId' => $editorId,
                'compact' => true,
            ])
        </div>
    </details>
@else
    <div class="mt-3{{ $hidden ? ' hidden' : '' }}" data-mermaid-source>
        @include('pages.partials.code-document', [
            'source' => $source,
            'language' => 'mermaid',
            'readonly' => true,
            'showCopy' => true,
            'editorId' => $editorId,
            'compact' => true,
        ])
    </div>
@endif
