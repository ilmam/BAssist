@extends(ui_layout())

@section('main')
    @php
        use App\Facades\Form;
        use App\Helpers\FormHelper;
        use App\Services\C4ArchitectureNormalizer;

        $modelName = class_basename($model);
        $verb = in_array($operation, ['insert', 'create'], true) ? 'POST' : 'PUT';
        $action = in_array($operation, ['insert', 'create'], true) ? 'store' : 'update';
        $title = ucfirst($operation).' '.$modelName;
        $route = model_route_name($model, $action);
        $cancelRoute = model_route_name($model, 'index');
        $formRoute = in_array($verb, ['POST', 'post'], true)
            ? ['route' => $route]
            : ['route' => [$route, $dto->id]];
        $normalizer = app(C4ArchitectureNormalizer::class);
        $elements = $normalizer->orderAsTree(
            $normalizer->normalizeElements(is_array($dto->elements ?? null) ? $dto->elements : [])
        );
        $relationships = is_array($dto->relationships ?? null) ? $dto->relationships : [];
        $layout = app(C4ArchitectureNormalizer::class)->normalizeLayout(
            is_array($dto->layout ?? null) ? $dto->layout : ($layout ?? [])
        );
    @endphp

    {{ Form::open(array_merge($formRoute, ['id' => 'form1', 'files' => true, 'method' => 'post'])) }}
        @if (! in_array($verb, ['POST', 'post'], true))
            @method($verb)
        @endif

        @if ($dto->id ?? null)
            {{ Form::hidden('id', $dto->id) }}
        @endif

        <div class="space-y-5">
            <x-card :title="$title">
                <x-slot:toolbar>
                    <x-button type="link" href="{{ $cancelRoute }}" icon="arrow-left" iconOnly="true" color="ghost" size="sm" activeColor="primary"></x-button>
                </x-slot:toolbar>

                <div class="space-y-5">
                    @foreach ($formFields as $name => $field)
                        @php
                            $fieldName = is_numeric($name) ? $field : $name;
                            $type = FormHelper::getFieldType($field);
                            $fieldValue = $dto->{$fieldName} ?? null;
                            $list = $field['list'] ?? null;
                            $options = [];
                        @endphp
                        {{ Form::field($type, $fieldName, $fieldValue, $list, $options ?: null) }}
                    @endforeach
                </div>
            </x-card>

            @include('pages.architectures.partials.architecture-editor', [
                'elements' => $elements,
                'relationships' => $relationships,
                'layout' => $layout,
                'features' => $features ?? [],
                'editable' => true,
                'mermaidContext' => $mermaidContext ?? null,
                'focusSystemKey' => $focusSystemKey ?? null,
                'focusContainerKey' => $focusContainerKey ?? null,
                'exportDslUrl' => $exportDslUrl ?? null,
                'exportJsonUrl' => $exportJsonUrl ?? null,
            ])

            <div class="flex justify-end gap-2.5">
                <x-button type="link" href="{{ $cancelRoute }}" color="outline">Cancel</x-button>
                <x-button type="submit" color="primary">Save</x-button>
            </div>
        </div>
    {{ Form::close() }}
@endsection
