@php
    $modelName = class_basename($model);
@endphp

<div class="space-y-4">
    @include('pages.partials.spine-cascade', [
        'cascade' => $cascade ?? null,
        'part' => 'before',
        'inModal' => $inModal ?? false,
    ])
    <x-details-view
        model="{{ $modelName }}"
        :dto="$dto"
        :fields="$fields">
    @include('pages.partials.spine-cascade', [
        'cascade' => $cascade ?? null,
        'part' => 'after',
        'inModal' => $inModal ?? false,
    ])
    </x-details-view>

    <section class="space-y-3">
        <h3 class="text-base font-semibold text-foreground">{{ __('ui.scenario_document') }}</h3>
        @include('pages.partials.gherkin-document', [
            'source' => $gherkin,
            'showCopy' => true,
            'editorId' => 'scenario_gherkin_'.$dto->id,
        ])
    </section>
</div>
