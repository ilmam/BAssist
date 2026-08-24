@php
    $modelName = class_basename($model);
    $entities = is_array($entities ?? null) ? $entities : (is_array($dto->entities ?? null) ? $dto->entities : []);
@endphp

<div class="space-y-8">
    <section class="space-y-3">
        <h4 class="text-sm font-semibold text-foreground">{{ __('ui.metadata') }}</h4>
        <x-details-view
            model="{{ $modelName }}"
            :dto="$dto"
            :fields="$fields"
        />
    </section>

    @include('pages.data_dictionaries.partials.dictionary-editor', [
        'entities' => $entities,
        'editable' => false,
        'autoRender' => true,
        'mermaid' => $mermaid ?? '',
        'mermaidConceptual' => $mermaidConceptual ?? '',
        'exportCsharpUrl' => $exportCsharpUrl ?? null,
        'exportPhpUrl' => $exportPhpUrl ?? null,
    ])
</div>
