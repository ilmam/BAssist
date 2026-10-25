{{-- Fields of this form, written once. Shells: ../form.blade.php (page) and ../modals/form.blade.php (pop-up). --}}
@php
    use App\Facades\Form;
    use App\Helpers\FormHelper;

    $modelName = class_basename($model);
    $inModal = (bool) ($inModal ?? false);
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

<x-record-form :model="$model" :dto="$dto" :title="$title" :form-route="$formRoute" :in-modal="$inModal" size="full" :cancel-url="$cancelRoute">
            @if (! $isCreate)
                @method($verb)
            @endif

            @if ($dto->id ?? null)
                {{ Form::hidden('id', $dto->id) }}
            @endif

            {{ Form::hidden('project_id', $dto->project_id ?? '') }}

            <x-section :title="__('ui.metadata')" icon="information-2" key="feature-details">
                <p class="text-sm text-muted-foreground mb-4">{{ __('ui.feature_traceability_help') }}</p>
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
            </x-section>

            {{-- Links: which need, change request or process step this feature belongs to. --}}
            <x-section :title="__('ui.feature_links_title')" icon="fasten" key="feature-links">
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
                    <div data-ui-span="12" data-ui-span-md="6" data-ui-span-lg="6">
                        {{ Form::field($type, 'stakeholder_need_id', $fieldValue, $list, $options) }}
                    </div>
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
            </x-section>

            <x-section :title="__('ui.feature_document')" icon="document" key="feature-document">
                <p class="text-sm text-muted-foreground mb-4">{{ __('ui.feature_document_edit_help') }}</p>
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
            </x-section>

            @if (array_key_exists('attachments', $formFields))
                <x-section :title="__('ui.attachments')" icon="paper-clip" key="feature-attachments">
                    <div class="form-fields-grid grid grid-cols-12">
                        @include('pages.partials.form-field-cells', [
                            'fields' => ['attachments' => $formFields['attachments']],
                            'dto' => $dto,
                            'entityModel' => $modelName,
                        ])
                    </div>
                </x-section>
            @endif


    <x-slot:after>
        @if (! $isCreate && ($dto->id ?? null))
            @php
                $addScenarioUrl = model_modal_path('Scenario', 'create').'?feature_id='.(int) $dto->id;
            @endphp
            <x-section :title="__('ui.scenarios')" icon="category" key="feature-scenarios" :meta="trans_choice('ui.scenarios_count', ($scenarios ?? collect())->count(), ['count' => ($scenarios ?? collect())->count()])">
                {{-- The action sits in the header, so it is there when the section is collapsed too. --}}
                @if (entity_can('Scenario', 'create'))
                    <x-slot:actions>
                        <x-button type="link" href="{{ $addScenarioUrl }}" color="primary" size="sm" class="js-open-modal" data-modal-url="{{ $addScenarioUrl }}">{{ __('ui.add_scenario') }}</x-button>
                    </x-slot:actions>
                @endif
                @include('pages.features.partials.scenarios-panel', [
                    'featureId' => $dto->id,
                    'scenarios' => $scenarios ?? collect(),
                    // Distinct editor ids: the pop-up can open on top of the page form.
                    'editorSuffix' => $inModal ? '_modal_edit' : '_edit',
                    'embedded' => true,
                ])
            </x-section>
        @else
            <p class="text-sm text-muted-foreground">{{ __('ui.feature_scenarios_after_create_help') }}</p>
        @endif
    </x-slot:after>
</x-record-form>
