@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
    @endphp

    <x-record-view :model="$model" :dto="$dto" :page-only="true">
        @include('pages.data_dictionaries.partials.view-content', [
            'dto' => $dto,
            'model' => $model,
            'fields' => $fields,
            'entities' => $entities ?? [],
            'mermaid' => $mermaid ?? '',
            'mermaidConceptual' => $mermaidConceptual ?? '',
            'exportCsharpUrl' => $exportCsharpUrl ?? null,
            'exportPhpUrl' => $exportPhpUrl ?? null,
        ])
    </x-record-view>
@endsection
