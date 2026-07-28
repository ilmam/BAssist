@php
    use App\Facades\Form;
    use App\Helpers\FormHelper;

    $modelName = class_basename($model);
    $verb = in_array($operation, ['insert', 'create'], true) ? 'POST' : 'PUT';
    $action = in_array($operation, ['insert', 'create'], true) ? 'store' : 'update';
    $isCreate = in_array($operation, ['insert', 'create'], true);
    $title = ucfirst($operation).' '.$modelName;
    $route = model_route_name($model, $action);
    $formRoute = $isCreate
        ? ['route' => $route]
        : ['route' => [$route, $dto->id]];

    $metaFields = ['title', 'feature_id', 'status_id', 'is_outline'];
    $documentFields = ['body'];
@endphp

<x-modal-content :title="$title" size="xl">
    {{ Form::open(array_merge($formRoute, [
        'id' => 'modalForm',
        'files' => true,
        'method' => 'post',
        'attributes' => [
            'data-modal-form' => 'true',
        ],
    ])) }}
        <div class="space-y-6">
            @if (! $isCreate)
                @method($verb)
            @endif

            @if ($dto->id ?? null)
                {{ Form::hidden('id', $dto->id) }}
            @endif

            <section class="space-y-4">
                <div class="form-section-intro">
                    <h3 class="text-sm font-semibold text-foreground">{{ __('ui.metadata') }}</h3>
                    <p class="text-xs text-muted-foreground">{{ __('ui.scenario_meta_help') }}</p>
                </div>
                <div class="form-fields">
                    @foreach ($metaFields as $fieldName)
                        @continue(! array_key_exists($fieldName, $formFields))
                        @php
                            $field = $formFields[$fieldName];
                            $type = FormHelper::getFieldType($field);
                            $fieldValue = $dto->{$fieldName} ?? null;
                            $list = $field['list'] ?? null;
                            $options = [];
                            if (! empty($field['help'])) {
                                $options['data-field-help'] = $field['help'];
                            }
                        @endphp
                        {{ Form::field($type, $fieldName, $fieldValue, $list, $options ?: null) }}
                    @endforeach
                </div>
            </section>

            <section class="space-y-4">
                <div class="form-section-intro">
                    <h3 class="text-sm font-semibold text-foreground">{{ __('ui.scenario_document') }}</h3>
                    <p class="text-xs text-muted-foreground">{{ __('ui.scenario_document_edit_help') }}</p>
                </div>
                @foreach ($documentFields as $fieldName)
                    @continue(! array_key_exists($fieldName, $formFields))
                    @php
                        $field = $formFields[$fieldName];
                        $type = FormHelper::getFieldType($field);
                        $fieldValue = $dto->{$fieldName} ?? null;
                        $options = [
                            'data-language' => $field['language'] ?? 'gherkin',
                            'data-field-help' => __('ui.gherkin_scenario_body_help'),
                        ];
                    @endphp
                    {{ Form::field($type, $fieldName, $fieldValue, null, $options) }}
                @endforeach
            </section>
        </div>

        <div class="flex justify-end gap-2.5 mt-5">
            <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Cancel</button>
            <x-button type="submit" color="primary">Save</x-button>
        </div>
    {{ Form::close() }}
</x-modal-content>
