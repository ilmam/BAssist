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
        $featureId = (int) ($dto->feature_id ?? request()->query('feature_id', 0));
        $cancelRoute = $featureId > 0
            ? model_route('Feature', 'show', $featureId)
            : model_route_name($model, 'index');
        $formRoute = $isCreate
            ? ['route' => $route]
            : ['route' => [$route, $dto->id]];

        $metaFields = ['title', 'feature_id', 'status_id', 'is_outline'];
        $documentFields = ['body'];
    @endphp

    <x-form-card :title="$title">
        <x-slot:toolbar>
            <x-button type="link" href="{{ $cancelRoute }}" icon="arrow-left" iconOnly="true" color="light" activeColor="primary"></x-button>
        </x-slot>

        {{ Form::open(array_merge($formRoute, ['id' => 'form1', 'files' => true, 'method' => 'post'])) }}
            <div class="kt-card-body border-t border-border p-5 lg:p-7.5 space-y-8">
                @if (! $isCreate)
                    @method($verb)
                @endif

                @if ($dto->id ?? null)
                    {{ Form::hidden('id', $dto->id) }}
                @endif

                <section class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-foreground">{{ __('ui.metadata') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ __('ui.scenario_meta_help') }}</p>
                    </div>
                    <div class="space-y-6">
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
                    <div>
                        <h3 class="text-base font-semibold text-foreground">{{ __('ui.scenario_document') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ __('ui.scenario_document_edit_help') }}</p>
                    </div>
                    <div class="space-y-6">
                        @foreach ($documentFields as $fieldName)
                            @continue(! array_key_exists($fieldName, $formFields))
                            @php
                                $field = $formFields[$fieldName];
                                $type = FormHelper::getFieldType($field);
                                $fieldValue = $dto->{$fieldName} ?? null;
                                $options = ['data-language' => $field['language'] ?? 'gherkin'];
                                $options['data-field-help'] = __('ui.gherkin_scenario_body_help');
                            @endphp
                            {{ Form::field($type, $fieldName, $fieldValue, null, $options) }}
                        @endforeach
                    </div>
                    <pre class="field-help whitespace-pre-wrap text-xs font-mono bg-muted/40 border border-border rounded-md p-3">{{ __('ui.gherkin_scenario_body_example') }}</pre>
                </section>
            </div>

            <div class="kt-card-footer flex justify-end gap-2.5 border-t border-border p-5 lg:p-7.5">
                <x-button type="link" href="{{ $cancelRoute }}" color="secondary">Cancel</x-button>
                <x-button type="submit" color="primary">Save</x-button>
            </div>
        {{ Form::close() }}
    </x-form-card>
@endsection
