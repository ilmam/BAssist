<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="text-sm text-muted-foreground">
            @if ($scenario->feature_id)
                <a class="kt-link" href="{{ model_route('Feature', 'show', $scenario->feature_id) }}">
                    {{ $scenario->feature?->code ? $scenario->feature->code.' — ' : '' }}{{ $scenario->feature?->title ?: __('ui.back_to_feature') }}
                </a>
            @endif
        </div>
        @include('pages.partials.gherkin-tags', ['tags' => $tagList ?? []])
    </div>

    @include('pages.partials.gherkin-document', [
        'source' => $gherkin,
        'showCopy' => true,
        'editorId' => 'scenario_gherkin_'.$dto->id,
    ])
</div>
