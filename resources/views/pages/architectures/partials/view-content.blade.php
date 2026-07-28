@php
    use App\Services\C4ArchitectureNormalizer;

    $normalizer = app(C4ArchitectureNormalizer::class);
    $elements = $normalizer->orderAsTree(
        $normalizer->normalizeElements(is_array($dto->elements ?? null) ? $dto->elements : [])
    );
    $relationships = is_array($dto->relationships ?? null) ? $dto->relationships : [];
    $pageTitle = $pageTitle ?? (class_basename($model).' Details');
@endphp

<div class="space-y-5">
    <x-card :title="$pageTitle">
        <x-slot:toolbar>
            @if (entity_can($model, 'update'))
                <x-button type="link" href="{{ model_route($model, 'edit', $dto->id) }}" icon="pencil" iconOnly="true" color="primary" activeColor="primary"></x-button>
            @endif
            @if ($exportDslUrl ?? null)
                <x-button type="link" href="{{ $exportDslUrl }}" color="light">{{ __('ui.c4_export_dsl') }}</x-button>
            @endif
            @if ($exportJsonUrl ?? null)
                <x-button type="link" href="{{ $exportJsonUrl }}" color="light">{{ __('ui.c4_export_json') }}</x-button>
            @endif
        </x-slot:toolbar>

        <x-details-view
            model="{{ class_basename($model) }}"
            :dto="$dto"
            :fields="$fields"
        />
    </x-card>

    @include('pages.architectures.partials.architecture-editor', [
        'elements' => $elements,
        'relationships' => $relationships,
        'layout' => $layout ?? ($dto->layout ?? []),
        'features' => $features ?? [],
        'editable' => false,
        'autoRender' => true,
        'mermaidContext' => $mermaidContext ?? null,
        'focusSystemKey' => $focusSystemKey ?? null,
        'focusContainerKey' => $focusContainerKey ?? null,
        'exportDslUrl' => $exportDslUrl ?? null,
        'exportJsonUrl' => $exportJsonUrl ?? null,
    ])
</div>
