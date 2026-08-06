@php
    $featureId = (int) ($featureId ?? $feature->id ?? $dto->id ?? 0);
    $scenarios = $scenarios ?? ($feature->scenarios ?? collect());
    $compact = (bool) ($compact ?? false);
    $headingClass = $compact ? 'text-sm font-semibold text-foreground' : 'text-base font-semibold text-foreground';
    $helpClass = $compact ? 'text-xs text-muted-foreground' : 'text-sm text-muted-foreground';
    $addScenarioModalUrl = $featureId > 0
        ? model_modal_path('Scenario', 'create').'?feature_id='.$featureId
        : null;
@endphp

<section class="space-y-4" data-feature-scenarios>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="form-section-intro space-y-1">
            <h3 class="{{ $headingClass }}">{{ __('ui.scenarios') }}</h3>
            <p class="{{ $helpClass }}">{{ __('ui.feature_scenarios_edit_help') }}</p>
        </div>
        @if ($addScenarioModalUrl && entity_can('Scenario', 'create'))
            <x-button
                type="link"
                href="{{ $addScenarioModalUrl }}"
                color="primary"
                class="js-open-modal"
                data-modal-url="{{ $addScenarioModalUrl }}"
            >{{ __('ui.add_scenario') }}</x-button>
        @endif
    </div>

    @forelse ($scenarios as $child)
        @php
            $viewScenarioModalUrl = model_modal_path('Scenario', 'view', $child->id);
            $editScenarioModalUrl = model_modal_path('Scenario', 'edit', $child->id);
        @endphp
        <div class="rounded-lg border border-border bg-muted/20 p-4 space-y-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h4 class="{{ $headingClass }}">
                    {{ $child->gherkinKeyword() }}: {{ $child->title }}
                </h4>
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
                'editorId' => 'scenario_body_'.$child->id.($editorSuffix ?? ''),
            ])
        </div>
    @empty
        <p class="{{ $helpClass }}">{{ __('ui.no_scenarios_yet') }}</p>
    @endforelse
</section>
