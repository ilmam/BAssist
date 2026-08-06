@php
    $modelName = class_basename($model);
@endphp

<div class="space-y-4">
    <x-details-view
        model="{{ $modelName }}"
        :dto="$dto"
        :fields="$fields"
    />

    @include('pages.partials.gherkin-document', [
        'source' => $gherkin,
        'showCopy' => true,
        'editorId' => 'scenario_gherkin_'.$dto->id,
    ])
</div>
