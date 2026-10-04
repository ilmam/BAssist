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
    $featureId = (int) ($dto->feature_id ?? request()->query('feature_id', 0));
    $cancelRoute = $featureId > 0
        ? model_route('Feature', 'show', $featureId)
        : model_route($model, 'index');
    $formRoute = $isCreate
        ? ['route' => $route]
        : ['route' => [$route, $dto->id]];

    $metaFields = ['title', 'status_id', 'is_outline'];
    $traceabilityFields = ['feature_id', 'stakeholder_need_id'];
    $documentFields = ['body'];
@endphp

<x-record-form :model="$model" :dto="$dto" :title="$title" :form-route="$formRoute" :in-modal="$inModal" size="full" :cancel-url="$cancelRoute">
            @if (! $isCreate)
                @method($verb)
            @endif

            @if ($dto->id ?? null)
                {{ Form::hidden('id', $dto->id) }}
            @endif

            <section class="space-y-4">
                <div class="form-section-intro">
                    <h3 class="text-base font-semibold text-foreground">{{ __('ui.metadata') }}</h3>
                    <p class="text-sm text-muted-foreground">{{ __('ui.scenario_meta_help') }}</p>
                </div>
            <div class="form-fields-grid grid grid-cols-12">
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
                        <div data-ui-span="12" data-ui-span-md="6" data-ui-span-lg="6">
                            {{ Form::field($type, $fieldName, $fieldValue, $list, $options ?: null) }}
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="form-section-box form-section-box--traceability">
                <div class="form-fields-grid grid grid-cols-12">
                    @foreach ($traceabilityFields as $fieldName)
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
                        <div data-ui-span="12" data-ui-span-md="6" data-ui-span-lg="6">
                            {{ Form::field($type, $fieldName, $fieldValue, $list, $options ?: null) }}
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="space-y-4">
                <div class="form-section-intro">
                    <h3 class="text-base font-semibold text-foreground">{{ __('ui.scenario_document') }}</h3>
                </div>
                <div class="form-fields">
                    @foreach ($documentFields as $fieldName)
                        @continue(! array_key_exists($fieldName, $formFields))
                        @php
                            $field = $formFields[$fieldName];
                            $type = FormHelper::getFieldType($field);
                            $fieldValue = $dto->{$fieldName} ?? null;
                            $options = [
                                'data-language' => $field['language'] ?? 'gherkin',
                                'data-field-help' => __('ui.gherkin_scenario_body_help'),
                                // Section heading is the single title; hide redundant ui.body ("Document").
                                'label' => '',
                            ];
                        @endphp
                        {{ Form::field($type, $fieldName, $fieldValue, null, $options) }}
                    @endforeach
                </div>
            </section>
</x-record-form>
