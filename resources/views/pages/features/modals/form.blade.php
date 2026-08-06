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

    $documentFields = ['body'];
@endphp

<x-modal-content :title="$title" size="full">
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

            {{ Form::hidden('project_id', $dto->project_id ?? '') }}

            <section class="space-y-4">
                <div class="form-section-intro">
                    <h3 class="text-sm font-semibold text-foreground">{{ __('ui.metadata') }}</h3>
                    <p class="text-xs text-muted-foreground">{{ __('ui.feature_traceability_help') }}</p>
                </div>
                <div class="form-fields-grid grid grid-cols-12">
                    @foreach (['code', 'title', 'priority_id', 'status_id'] as $fieldName)
                        @continue(! array_key_exists($fieldName, $formFields))
                        @php
                            $field = $formFields[$fieldName];
                            $type = FormHelper::getFieldType($field);
                            $fieldValue = $dto->{$fieldName} ?? null;
                            $list = $field['list'] ?? null;
                            $options = [];
                            if (! empty($field['readonly'])) {
                                $options['readonly'] = 'readonly';
                                $options['disabled'] = 'disabled';
                                if (blank($fieldValue)) {
                                    $options['placeholder'] = __('ui.code_assigned_on_save');
                                }
                            }
                            if (! empty($field['help'])) {
                                $options['data-field-help'] = $field['help'];
                            }
                        @endphp
                        <div data-ui-span="12" data-ui-span-md="6" data-ui-span-lg="6">
                            {{ Form::field($type, $fieldName, $fieldValue, $list, $options ?: null) }}
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="form-section-box form-section-box--traceability">
                <div class="form-fields-grid grid grid-cols-12">
                @if (array_key_exists('stakeholder_need_id', $formFields))
                    @php
                        $field = $formFields['stakeholder_need_id'];
                        $type = FormHelper::getFieldType($field);
                        $fieldValue = $dto->stakeholder_need_id ?? null;
                        $list = $field['list'] ?? null;
                        $options = [
                            'data-field-help' => __('ui.stakeholder_need_field_help'),
                        ];
                    @endphp
                    <div data-ui-span="12" data-ui-span-md="12" data-ui-span-lg="12">
                        {{ Form::field($type, 'stakeholder_need_id', $fieldValue, $list, $options) }}
                    </div>
                @endif
                @if (array_key_exists('change_request_id', $formFields))
                    @php
                        $field = $formFields['change_request_id'];
                        $type = FormHelper::getFieldType($field);
                        $fieldValue = $dto->change_request_id ?? null;
                        $list = $field['list'] ?? null;
                        $options = [];
                        if (! empty($field['help'])) {
                            $options['data-field-help'] = $field['help'];
                        }
                    @endphp
                    <div data-ui-span="12" data-ui-span-md="6" data-ui-span-lg="6">
                        {{ Form::field($type, 'change_request_id', $fieldValue, $list, $options ?: null) }}
                    </div>
                @endif
                @if (array_key_exists('swimlane_flow_step_id', $formFields))
                    @php
                        $field = $formFields['swimlane_flow_step_id'];
                        $type = FormHelper::getFieldType($field);
                        $fieldValue = $dto->swimlane_flow_step_id ?? null;
                        $list = $field['list'] ?? null;
                        $options = [];
                        if (! empty($field['help'])) {
                            $options['data-field-help'] = $field['help'];
                        }
                    @endphp
                    <div data-ui-span="12" data-ui-span-md="6" data-ui-span-lg="6">
                        {{ Form::field($type, 'swimlane_flow_step_id', $fieldValue, $list, $options ?: null) }}
                    </div>
                @endif
                </div>
            </section>

            <section class="space-y-4">
                <div class="form-section-intro">
                    <h3 class="text-sm font-semibold text-foreground">{{ __('ui.feature_document') }}</h3>
                    <p class="text-xs text-muted-foreground">{{ __('ui.feature_document_edit_help') }}</p>
                </div>
                @foreach ($documentFields as $fieldName)
                    @continue(! array_key_exists($fieldName, $formFields))
                    @php
                        $field = $formFields[$fieldName];
                        $type = FormHelper::getFieldType($field);
                        $fieldValue = $dto->{$fieldName} ?? null;
                        $options = [
                            'data-language' => $field['language'] ?? 'gherkin',
                            'data-field-help' => __('ui.gherkin_feature_body_help'),
                        ];
                    @endphp
                    {{ Form::field($type, $fieldName, $fieldValue, null, $options) }}
                @endforeach
            </section>

        </div>

        <div class="flex justify-end gap-2.5 mt-5">
            <x-button type="button" color="outline" data-kt-modal-dismiss="true">Cancel</x-button>
            <x-button type="submit" color="primary">Save</x-button>
        </div>
    {{ Form::close() }}

    @if (! $isCreate && ($dto->id ?? null))
        <div class="mt-6 border-t border-border pt-6">
            @include('pages.features.partials.scenarios-panel', [
                'featureId' => $dto->id,
                'scenarios' => $scenarios ?? collect(),
                'compact' => true,
                'editorSuffix' => '_modal_edit',
            ])
        </div>
    @elseif ($isCreate)
        <p class="mt-4 text-xs text-muted-foreground">{{ __('ui.feature_scenarios_after_create_help') }}</p>
    @endif
</x-modal-content>
