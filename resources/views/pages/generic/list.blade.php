@extends(ui_layout())

@section('main')
    @php
        use Illuminate\Support\Str;

        $listFilters = $listFilters ?? [];
        $ajaxUrl = route('api.'.Str::snake($model).'.index', ['modelName' => $model]);
        if ($listFilters !== []) {
            $ajaxUrl .= (str_contains($ajaxUrl, '?') ? '&' : '?').http_build_query($listFilters);
        }

        $options = array_merge([
            'columns' => $columns,
            'keys' => ['id'],
            'tableClass' => 'table-hover table-striped',
            'dataRoute' => 'api.'.Str::snake($model).'.index',
            'model' => $model,
            'dataRoutParameters' => ['modelName' => $model],
            'ajaxUrl' => $ajaxUrl,
        ], $datatableOptions ?? []);

        if (! isset($options['quickEdit']) && entity_can($model, 'update')) {
            $options['quickEdit'] = \App\Http\Controllers\QuickActionsController::optionsFor($model);
        }

        $entityLabel = (string) (\App\Support\CrudEntityRegistry::all()[$model]['nav_label'] ?? Str::headline(Str::plural($model)));

        if (! isset($options['emptyStateHtml'])) {
            $canCreate = entity_can($model, 'create');
            $modelAllowsModals = config('crud.models.'.class_basename($model).'.use_modals', true) !== false;
            $options['emptyStateHtml'] = \Illuminate\Support\Facades\Blade::render(
                <<<'BLADE'
                <x-empty-state :icon="$icon" :title="$title" :hint="$hint" compact>
                    <x-slot:actions>
                        @if ($canCreate)
                            <a href="{{ $createUrl }}" class="{{ ui_btn_classes('primary', 'sm') }}" @if ($modalUrl) data-modal-url="{{ $modalUrl }}" @endif>
                                <i class="ki-filled ki-plus"></i>{{ $createLabel }}
                            </a>
                        @endif
                        @if ($helpUrl)
                            <a href="{{ $helpUrl }}" class="{{ ui_btn_classes('outline', 'sm') }}" target="_blank" rel="noopener">
                                <i class="ki-filled ki-book-open"></i>{{ $helpLabel }}
                            </a>
                        @endif
                    </x-slot:actions>
                </x-empty-state>
                BLADE,
                [
                    'icon' => entity_icon($model, 'questionnaire-tablet'),
                    'title' => __('ui.list_empty_entity_title', ['entity' => $entityLabel]),
                    'hint' => \App\Support\HelpRegistry::summaryForModel($model) ?? __('ui.list_empty_hint'),
                    'canCreate' => $canCreate,
                    'createUrl' => model_route($model, 'create'),
                    'modalUrl' => config('ui.modal_create', true) && $modelAllowsModals ? model_modal_path($model, 'create') : null,
                    'createLabel' => __('ui.list_empty_create', ['entity' => Str::singular($entityLabel)]),
                    'helpUrl' => help_url($model),
                    'helpLabel' => __('ui.list_empty_learn'),
                ],
            );
        }
    @endphp

    <x-card title="{{ $entityLabel }}">
        <x-slot:titleAside>
            <x-help-trigger :model="$model" />
        </x-slot:titleAside>
        <x-slot:toolbar>
            <div class="flex items-center gap-2">
                @include('pages.partials.create-toolbar-button', ['model' => $model])
            </div>
        </x-slot>
        <div class="ba-saved-views" data-saved-views="{{ $model }}"
             data-saved-views-placeholder="{{ __('ui.saved_views_placeholder') }}"
             data-saved-views-empty="{{ __('ui.saved_views_empty') }}">
            <span class="ba-saved-views__label"><i class="ki-filled ki-bookmark" aria-hidden="true"></i>{{ __('ui.saved_views') }}</span>
            <span class="ba-saved-views__list" data-saved-views-list></span>
            <label class="sr-only" for="saved-view-name-{{ $model }}">{{ __('ui.saved_views_placeholder') }}</label>
            <input id="saved-view-name-{{ $model }}" type="text" maxlength="40" class="kt-input kt-input-sm ba-saved-views__input" data-saved-views-name>
            <button type="button" class="{{ ui_btn_classes('outline', 'sm') }}" data-saved-views-save>{{ __('ui.saved_views_save') }}</button>
        </div>
        <x-datatable
            :options="$options"
            :defaultButtons="true"
            :collapsedActions="$options['collapsedActions'] ?? null"
        />
    </x-card>
@endsection

@push('scripts')
    @vite(['resources/js/list-power.js'])
@endpush
