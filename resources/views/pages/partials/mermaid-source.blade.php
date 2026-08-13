@php
    $source = $source ?? '';
    $editorId = $editorId ?? ('mermaid_source_'.uniqid());
    $hidden = (bool) ($hidden ?? false);
    $wrapInDetails = (bool) ($wrapInDetails ?? true);
    $summary = $summary ?? __('ui.mermaid_source');
    $readonly = (bool) ($readonly ?? true);
    $showApply = (bool) ($showApply ?? false);
@endphp

@if ($wrapInDetails)
    <details class="mt-3">
        <summary class="text-xs text-muted-foreground cursor-pointer">{{ $summary }}</summary>
        <div class="mt-2" data-mermaid-source>
            @include('pages.partials.code-document', [
                'source' => $source,
                'language' => 'mermaid',
                'readonly' => $readonly,
                'showCopy' => true,
                'editorId' => $editorId,
                'compact' => true,
            ])
            @if ($showApply)
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-apply-mermaid-source>
                        {{ __('ui.apply_mermaid_source') }}
                    </button>
                    <p class="text-xs text-muted-foreground m-0" data-mermaid-apply-help>
                        {{ __('ui.apply_mermaid_source_help') }}
                    </p>
                    <p class="text-xs m-0 w-full hidden" data-mermaid-apply-status role="status" aria-live="polite"></p>
                </div>
            @endif
        </div>
    </details>
@else
    <div class="mt-3{{ $hidden ? ' hidden' : '' }}" data-mermaid-source>
        @include('pages.partials.code-document', [
            'source' => $source,
            'language' => 'mermaid',
            'readonly' => $readonly,
            'showCopy' => true,
            'editorId' => $editorId,
            'compact' => true,
        ])
        @if ($showApply)
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-apply-mermaid-source>
                    {{ __('ui.apply_mermaid_source') }}
                </button>
                <p class="text-xs text-muted-foreground m-0" data-mermaid-apply-help>
                    {{ __('ui.apply_mermaid_source_help') }}
                </p>
                <p class="text-xs m-0 w-full hidden" data-mermaid-apply-status role="status" aria-live="polite"></p>
            </div>
        @endif
    </div>
@endif
