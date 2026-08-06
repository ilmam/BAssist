@php
    $featureBody = (string) ($feature->body ?? '');
    $assembledGherkin = $assembledGherkin ?? '';
    $editFeatureModalUrl = model_modal_path($model, 'edit', $dto->id);
    $rawDialogId = 'feature_raw_'.$dto->id;
    $assembledExportId = 'assembled_export_'.$dto->id;
    $modelName = class_basename($model);
@endphp

<div class="space-y-6" data-feature-page>
    {{-- Metadata: same field chrome as FR / Stakeholder Need / generic entities --}}
    <x-details-view
        model="{{ $modelName }}"
        :dto="$dto"
        :fields="$fields"
    />

    {{-- Feature-specific export / import actions --}}
    <div class="flex flex-wrap gap-2">
        @if (filled($assembledGherkin))
            <button
                type="button"
                class="kt-btn kt-btn-sm kt-btn-primary"
                data-feature-raw-open="{{ $rawDialogId }}"
            >{{ __('ui.view_raw') }}</button>
            <button
                type="button"
                class="kt-btn kt-btn-sm kt-btn-outline"
                data-clipboard-from="#{{ $assembledExportId }}"
            >{{ __('ui.copy_gherkin') }}</button>
        @endif
        @if (! empty($exportUrl))
            <x-button type="link" href="{{ $exportUrl }}" color="light">{{ __('ui.download_feature') }}</x-button>
        @endif
        @if (! empty($printUrl))
            <x-button type="link" href="{{ $printUrl }}" color="light" target="_blank">{{ __('ui.print_feature') }}</x-button>
        @endif
        @if (! empty($importUrl) && entity_can($model, 'update'))
            <x-button type="link" href="{{ $importUrl }}" color="primary">{{ __('ui.import_feature_file') }}</x-button>
        @endif
    </div>
    @if (filled($assembledGherkin))
        <script type="application/json" id="{{ $assembledExportId }}">@json($assembledGherkin)</script>
    @endif

    {{-- Feature body document --}}
    <section class="space-y-3">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <h3 class="text-base font-semibold text-foreground">{{ __('ui.feature_document') }}</h3>
            <div class="flex flex-wrap gap-2">
                @if (entity_can($model, 'update'))
                    <x-button
                        type="link"
                        href="{{ $editFeatureModalUrl }}"
                        color="primary"
                        class="js-open-modal"
                        data-modal-url="{{ $editFeatureModalUrl }}"
                    >{{ __('ui.edit') }}</x-button>
                @endif
            </div>
        </div>

        @include('pages.partials.gherkin-document', [
            'source' => $featureBody,
            'showCopy' => true,
            'editorId' => 'feature_body_'.$dto->id,
        ])
    </section>

    @include('pages.features.partials.scenarios-panel', [
        'featureId' => $dto->id,
        'feature' => $feature,
        'scenarios' => $feature->scenarios ?? collect(),
        'editorSuffix' => '_view',
    ])

    @if (filled($assembledGherkin))
        <dialog id="{{ $rawDialogId }}" class="feature-raw-dialog">
            <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-3 shrink-0">
                <div>
                    <h3 class="text-base font-semibold text-foreground">{{ __('ui.view_raw') }}</h3>
                    <p class="text-xs text-muted-foreground">{{ __('ui.view_raw_help') }}</p>
                </div>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-feature-raw-close aria-label="{{ __('ui.close') }}">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="p-4 overflow-y-auto min-h-0 flex-1">
                @include('pages.partials.gherkin-document', [
                    'source' => $assembledGherkin,
                    'showCopy' => true,
                    'downloadUrl' => $exportUrl ?? null,
                    'editorId' => 'feature_raw_body_'.$dto->id,
                ])
            </div>
            <div class="flex flex-wrap justify-end gap-2 border-t border-border px-4 py-3 shrink-0">
                <button type="button" class="kt-btn kt-btn-outline" data-clipboard-from="#{{ $assembledExportId }}">
                    {{ __('ui.copy_gherkin') }}
                </button>
                @if (! empty($exportUrl))
                    <x-button type="link" href="{{ $exportUrl }}" color="light">{{ __('ui.download_feature') }}</x-button>
                @endif
                @if (! empty($printUrl))
                    <x-button type="link" href="{{ $printUrl }}" color="light" target="_blank">{{ __('ui.print_feature') }}</x-button>
                @endif
                <button type="button" class="kt-btn kt-btn-primary" data-feature-raw-close>{{ __('ui.close') }}</button>
            </div>
        </dialog>
    @endif
</div>
