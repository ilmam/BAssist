{{-- Fields of this form, written once. Shells: ../form.blade.php (page) and ../modals/form.blade.php (pop-up). --}}
@php
    use App\Facades\Form;
    use App\Helpers\FormHelper;

    $modelName = class_basename($model);
    $inModal = (bool) ($inModal ?? false);
    $verb = in_array($operation, ['insert', 'create'], true) ? 'POST' : 'PUT';
    $action = in_array($operation, ['insert', 'create'], true) ? 'store' : 'update';
    $title = ucfirst($operation).' '.$modelName;
    $route = model_route_name($model, $action);
    $cancelRoute = model_route($model, 'index');
    $formRoute = in_array($verb, ['POST', 'post'], true)
        ? ['route' => $route]
        : ['route' => [$route, $dto->id]];
    $elements = is_array($dto->elements ?? null) ? $dto->elements : [];
@endphp

<x-record-form :model="$model" :dto="$dto" :title="$title" :form-route="$formRoute" :in-modal="$inModal" size="fullscreen" :cancel-url="$cancelRoute">
            @if (! in_array($verb, ['POST', 'post'], true))
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
                        $options = [];
                        $isWide = in_array($type, ['textarea', 'code', 'dropzone'], true);
                    @endphp
                    <div data-ui-span="12" data-ui-span-md="{{ $isWide ? 12 : 6 }}" data-ui-span-lg="{{ $isWide ? 12 : 6 }}">
                        {{ Form::field($type, $fieldName, $fieldValue, $list, $options ?: null) }}
                    </div>
                @endforeach
            </div>

            @include('pages.swimlane_flows.partials.elements-editor', [
                'elements' => $elements,
                'direction' => $dto->direction ?? 'TB',
                'colorMode' => $dto->color_mode ?? 'both',
                'editable' => true,
                'showTitleField' => false,
                'stakeholderNeedOptions' => $stakeholderNeedOptions ?? [],
                'stakeholderNeedOptionsUrl' => $stakeholderNeedOptionsUrl ?? route('swimlane_flows.stakeholder-need-options'),
            ])
</x-record-form>
