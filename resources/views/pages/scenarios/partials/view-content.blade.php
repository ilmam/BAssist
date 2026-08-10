@php
    $modelName = class_basename($model);
@endphp

<div class="space-y-4">
    <x-details-view
        model="{{ $modelName }}"
        :dto="$dto"
        :fields="$fields"
    />

    <section class="space-y-3">
        <h3 class="text-base font-semibold text-foreground">{{ __('ui.scenario_document') }}</h3>
        @include('pages.partials.gherkin-document', [
            'source' => $gherkin,
            'showCopy' => true,
            'editorId' => 'scenario_gherkin_'.$dto->id,
        ])
    </section>
</div>
