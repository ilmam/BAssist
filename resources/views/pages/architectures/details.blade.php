@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
    @endphp

    <div class="space-y-5">
        @include('pages.architectures.partials.view-content', [
            'dto' => $dto,
            'model' => $model,
            'fields' => $fields,
            'features' => $features ?? [],
            'mermaidContext' => $mermaidContext ?? null,
            'mermaidContainer' => $mermaidContainer ?? null,
            'mermaidComponent' => $mermaidComponent ?? null,
            'layout' => $layout ?? null,
            'focusSystemKey' => $focusSystemKey ?? null,
            'focusContainerKey' => $focusContainerKey ?? null,
            'exportDslUrl' => $exportDslUrl ?? null,
            'exportJsonUrl' => $exportJsonUrl ?? null,
            'pageTitle' => $modelName.' Details',
        ])

        <div class="flex justify-end gap-2.5">
            <x-button type="link" href="{{ route('diagrams.index', ['project_id' => $dto->project_id]) }}" color="light">{{ __('ui.diagrams') }}</x-button>
            @if (entity_can($model, 'update'))
                <x-button type="link" href="{{ model_route($model, 'edit', $dto->id) }}" color="primary">Edit</x-button>
            @endif
        </div>
    </div>
@endsection
