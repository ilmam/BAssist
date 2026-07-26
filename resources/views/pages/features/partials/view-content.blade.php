@php
    $featureBody = (string) ($feature->body ?? '');
    $assembledGherkin = $assembledGherkin ?? '';
    $editFeatureModalUrl = model_modal_path($model, 'edit', $dto->id);
    $addScenarioModalUrl = model_modal_path('Scenario', 'create').'?feature_id='.$dto->id;
    $rawDialogId = 'feature_raw_'.$dto->id;
    $assembledExportId = 'assembled_export_'.$dto->id;
@endphp

<div class="space-y-6" data-feature-page>
    {{-- 1. Compact feature metadata + export actions (no always-visible full-file block) --}}
    <section class="rounded-lg border border-border bg-muted/30 p-4 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 text-sm">
            <div>
                <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">{{ __('ui.code') }}</div>
                <div class="text-foreground">{{ $feature->code ?: '—' }}</div>
            </div>
            <div class="sm:col-span-2">
                <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">{{ __('ui.title') }}</div>
                <div class="text-foreground">{{ $feature->title ?: '—' }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">{{ __('ui.project') }}</div>
                <div class="text-foreground">{{ $feature->project?->name ?: '—' }}</div>
            </div>
            <div class="sm:col-span-2 rounded-md border border-primary/25 bg-background/80 px-3 py-2">
                <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                    {{ __('ui.traceability') }} · {{ __('ui.stakeholder_need') }}
                </div>
                <div class="text-foreground mt-0.5">
                    @if ($feature->stakeholderNeed)
                        @if (entity_can('StakeholderNeed', 'view'))
                            <a class="kt-link" href="{{ model_route('StakeholderNeed', 'show', $feature->stakeholder_need_id) }}">
                                {{ $feature->stakeholderNeed->code ? $feature->stakeholderNeed->code.' — ' : '' }}{{ $feature->stakeholderNeed->title }}
                            </a>
                        @else
                            {{ $feature->stakeholderNeed->code ? $feature->stakeholderNeed->code.' — ' : '' }}{{ $feature->stakeholderNeed->title }}
                        @endif
                    @else
                        <span class="text-muted-foreground">{{ __('ui.no_stakeholder_need_linked') }}</span>
                    @endif
                </div>
                <p class="text-xs text-muted-foreground mt-1">{{ __('ui.stakeholder_need_field_help') }}</p>
            </div>
            <div>
                <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">{{ __('ui.status') }}</div>
                <div class="text-foreground">{{ $feature->status?->name ?: '—' }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">{{ __('ui.priority') }}</div>
                <div class="text-foreground">{{ $feature->priority?->name ?: '—' }}</div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 border-t border-border pt-3">
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
        </div>
        @if (filled($assembledGherkin))
            {{-- Non-rendered copy buffer (never a visible control in the toolbar) --}}
            <script type="application/json" id="{{ $assembledExportId }}">@json($assembledGherkin)</script>
        @endif
    </section>

    {{-- 2. Feature body document only --}}
    <section class="space-y-3">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="space-y-1">
                <h3 class="text-base font-semibold text-foreground">{{ __('ui.feature_document') }}</h3>
                @include('pages.partials.gherkin-tags', ['tags' => $tagList ?? []])
            </div>
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
                @if (entity_can('Scenario', 'create'))
                    <x-button
                        type="link"
                        href="{{ $addScenarioModalUrl }}"
                        color="light"
                        class="js-open-modal"
                        data-modal-url="{{ $addScenarioModalUrl }}"
                    >{{ __('ui.add_scenario') }}</x-button>
                @endif
            </div>
        </div>

        @include('pages.partials.gherkin-document', [
            'source' => $featureBody,
            'showCopy' => true,
            'editorId' => 'feature_body_'.$dto->id,
        ])
    </section>

    {{-- 3+. Each scenario body --}}
    @php
        $gherkinAssembler = app(\App\Services\GherkinFeatureAssembler::class);
    @endphp
    @forelse ($feature->scenarios ?? [] as $child)
        @php
            $viewScenarioModalUrl = model_modal_path('Scenario', 'view', $child->id);
            $editScenarioModalUrl = model_modal_path('Scenario', 'edit', $child->id);
            $scenarioTags = $gherkinAssembler->scenarioDisplayTags($child);
        @endphp
        <section class="space-y-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="space-y-1">
                    <h3 class="text-base font-semibold text-foreground">
                        {{ $child->gherkinKeyword() }}: {{ $child->title }}
                    </h3>
                    @include('pages.partials.gherkin-tags', ['tags' => $scenarioTags])
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-button
                        type="link"
                        href="{{ $viewScenarioModalUrl }}"
                        color="light"
                        class="js-open-modal"
                        data-modal-url="{{ $viewScenarioModalUrl }}"
                    >{{ __('ui.view') }}</x-button>
                    @if (entity_can('Scenario', 'update'))
                        <x-button
                            type="link"
                            href="{{ $editScenarioModalUrl }}"
                            color="primary"
                            class="js-open-modal"
                            data-modal-url="{{ $editScenarioModalUrl }}"
                        >{{ __('ui.edit') }}</x-button>
                    @endif
                    @if (entity_can('Scenario', 'delete'))
                        <x-button
                            type="link"
                            href="{{ model_modal_path('Scenario', 'delete', $child->id) }}"
                            color="danger"
                            class="js-open-modal"
                            data-modal-url="{{ model_modal_path('Scenario', 'delete', $child->id) }}"
                        >{{ __('ui.delete') }}</x-button>
                    @endif
                </div>
            </div>

            @include('pages.partials.gherkin-document', [
                'source' => (string) ($child->body ?? ''),
                'showCopy' => true,
                'editorId' => 'scenario_body_'.$child->id,
            ])
        </section>
    @empty
        <p class="text-sm text-muted-foreground">{{ __('ui.no_scenarios_yet') }}</p>
    @endforelse

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
