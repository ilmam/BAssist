@php
    $title = ($feature->code ? $feature->code.' — ' : '').$feature->title;
@endphp

<x-modal-content :title="$title" size="lg">
    <div class="space-y-3" data-feature-page>
        <p class="text-xs text-muted-foreground">{{ __('ui.view_raw_help') }}</p>

        @if (filled($assembledGherkin))
            @include('pages.partials.gherkin-document', [
                'source' => $assembledGherkin,
                'showCopy' => true,
                'downloadUrl' => $exportUrl ?? null,
                'editorId' => 'feature_raw_modal_body_'.$feature->id,
            ])
        @endif
    </div>

    <x-slot:footer>
        <x-modal-dismiss text="Close" />
    </x-slot:footer>
</x-modal-content>
