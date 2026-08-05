@extends(ui_layout())

@section('main')
    @php
        use App\Facades\Form;
        use App\Helpers\FormHelper;

        $modelName = class_basename($model);
        $verb = in_array($operation, ['insert', 'create'], true) ? 'POST' : 'PUT';
        $action = in_array($operation, ['insert', 'create'], true) ? 'store' : 'update';
        $isCreate = in_array($operation, ['insert', 'create'], true);
        $title = ucfirst($operation).' '.$modelName;
        $route = model_route_name($model, $action);
        $cancelRoute = $isCreate
            ? model_route($model, 'index')
            : model_route($model, 'show', $dto->id);
        $formRoute = $isCreate
            ? ['route' => $route]
            : ['route' => [$route, $dto->id]];

        $documentFields = ['body'];
    @endphp

    <x-form-card :title="$title">
        <x-slot:toolbar>
            <x-button type="link" href="{{ $cancelRoute }}" icon="arrow-left" iconOnly="true" color="light" activeColor="primary"></x-button>
        </x-slot>

        {{ Form::open(array_merge($formRoute, ['id' => 'form1', 'files' => true, 'method' => 'post'])) }}
            <div class="kt-card-body border-t border-border p-5 lg:p-7.5 space-y-8" data-ui-container>
                @if (! $isCreate)
                    @method($verb)
                @endif

                @if ($dto->id ?? null)
                    {{ Form::hidden('id', $dto->id) }}
                @endif

                <section class="space-y-4">
                    <div class="form-section-intro">
                        <h3 class="text-base font-semibold text-foreground">{{ __('ui.metadata') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ __('ui.feature_traceability_help') }}</p>
                    </div>
                    <div class="form-fields-grid grid grid-cols-12">
                        @foreach (['code', 'title', 'project_id', 'priority_id', 'status_id'] as $fieldName)
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

                <section class="space-y-4 rounded-lg border border-primary/30 bg-muted/20 p-4">
                    <div class="form-section-intro">
                        <h3 class="text-base font-semibold text-foreground">{{ __('ui.traceability') }} · {{ __('ui.stakeholder_need') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ __('ui.traceability_section_help') }}</p>
                    </div>
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
                        {{ Form::field($type, 'stakeholder_need_id', $fieldValue, $list, $options) }}
                    @else
                        <p class="text-sm text-danger">{{ __('ui.stakeholder_need') }} field is missing from the form definition.</p>
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
                        {{ Form::field($type, 'change_request_id', $fieldValue, $list, $options ?: null) }}
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
                        {{ Form::field($type, 'swimlane_flow_step_id', $fieldValue, $list, $options ?: null) }}
                    @endif
                </section>

                <section class="space-y-4">
                    <div class="form-section-intro">
                        <h3 class="text-base font-semibold text-foreground">{{ __('ui.feature_document') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ __('ui.feature_document_edit_help') }}</p>
                    </div>
                    <div class="form-fields">
                        @foreach ($documentFields as $fieldName)
                            @continue(! array_key_exists($fieldName, $formFields))
                            @php
                                $field = $formFields[$fieldName];
                                $type = FormHelper::getFieldType($field);
                                $fieldValue = $dto->{$fieldName} ?? null;
                                $options = [];
                                if ($type === 'code' && ! empty($field['language'])) {
                                    $options['data-language'] = $field['language'];
                                }
                                $options['data-field-help'] = __('ui.gherkin_feature_body_help');
                            @endphp
                            {{ Form::field($type, $fieldName, $fieldValue, null, $options) }}
                        @endforeach
                    </div>
                    <pre class="field-help whitespace-pre-wrap text-xs font-mono bg-muted/40 border border-border rounded-md p-3">{{ __('ui.gherkin_feature_body_example') }}</pre>
                </section>

            </div>

            <div class="kt-card-footer flex justify-end gap-2.5 border-t border-border p-5 lg:p-7.5">
                <x-button type="link" href="{{ $cancelRoute }}" color="secondary">Cancel</x-button>
                <x-button type="submit" color="primary">Save</x-button>
            </div>
        {{ Form::close() }}
    </x-form-card>
@endsection
