{{-- Screen create / edit: the usual fields, then the element rows with a live preview. Full page only (use_modals is off). --}}
@php
    use App\Facades\Form;
    use App\Helpers\FormHelper;

    $modelName = class_basename($model);
    $verb = in_array($operation, ['insert', 'create'], true) ? 'POST' : 'PUT';
    $action = in_array($operation, ['insert', 'create'], true) ? 'store' : 'update';
    $title = ucfirst($operation).' '.$modelName;
    $route = model_route_name($model, $action);
    $cancelRoute = model_route($model, 'index');
    $formRoute = $verb === 'POST'
        ? ['route' => $route]
        : ['route' => [$route, $dto->id]];
    $elements = is_array($dto->elements ?? null) ? $dto->elements : [];
@endphp

<x-record-form :model="$model" :dto="$dto" :title="$title" :form-route="$formRoute" :in-modal="false" size="fullscreen" :cancel-url="$cancelRoute">
    @if ($verb !== 'POST')
        @method($verb)
    @endif

    @if ($dto->id ?? null)
        {{ Form::hidden('id', $dto->id) }}
    @endif

    <div class="form-fields-grid grid grid-cols-12">
        @foreach ($formFields as $name => $field)
            @php
                $fieldName = is_numeric($name) ? $field : $name;
                $type = FormHelper::getFieldType($field);
                $fieldValue = $dto->{$fieldName} ?? null;
                $list = $field['list'] ?? null;
                $isWide = in_array($type, ['textarea', 'code', 'dropzone'], true);
            @endphp
            <div data-ui-span="12" data-ui-span-md="{{ $isWide ? 12 : 6 }}" data-ui-span-lg="{{ $isWide ? 12 : 6 }}">
                {{ Form::field($type, $fieldName, $fieldValue, $list, null) }}
            </div>
        @endforeach
    </div>

    @include('pages.screens.partials.elements-designer', [
        'elements' => $elements,
        'projectId' => (int) ($dto->project_id ?? 0),
        'previewUrl' => route('screens.preview'),
    ])
</x-record-form>
