@php
    $title = ($feature->code ? $feature->code.' — ' : '').$feature->title;
@endphp

<x-modal-content :title="$title" size="lg">
    <div class="space-y-3" data-feature-page>
        <p class="text-xs text-muted-foreground">{{ __('ui.view_raw_help') }}</p>

        @if (filled($assembledGherkin))
            <script type="application/json" id="assembled_export_{{ $feature->id }}">@json($assembledGherkin)</script>
            @include('pages.partials.gherkin-document', [
                'source' => $assembledGherkin,
                'showCopy' => true,
                'downloadUrl' => $exportUrl ?? null,
                'editorId' => 'feature_raw_modal_body_'.$feature->id,
            ])
        @endif
    </div>

    <x-slot:footer>
        @if (filled($assembledGherkin))
            <button type="button" class="kt-btn kt-btn-outline" data-clipboard-from="#assembled_export_{{ $feature->id }}">
                {{ __('ui.copy_gherkin') }}
            </button>
            @if (! empty($exportUrl))
                <x-button type="link" href="{{ $exportUrl }}" color="light">{{ __('ui.download_feature') }}</x-button>
            @endif
            @if (! empty($printUrl))
                <x-button type="link" href="{{ $printUrl }}" color="light" target="_blank">{{ __('ui.print_feature') }}</x-button>
            @endif
        @endif
        <x-modal-dismiss text="Close" />
    </x-slot:footer>
</x-modal-content>
