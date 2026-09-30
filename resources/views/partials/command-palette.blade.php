{{-- Ctrl/⌘+K command palette (#6): jump to any record by code or title, open pages, create items. --}}
@auth
@php
    $paletteCommands = [
        ['type' => __('ui.palette_go'), 'title' => __('ui.home_title'), 'icon' => 'element-11', 'url' => route('home')],
        ['type' => __('ui.palette_go'), 'title' => __('ui.traceability_matrix'), 'icon' => 'fasten', 'url' => route('traceability.index')],
        ['type' => __('ui.palette_go'), 'title' => __('ui.solution_requirements'), 'icon' => 'subtitle', 'url' => route('solution_requirements.index')],
        ['type' => __('ui.palette_go'), 'title' => __('ui.help_guide_title'), 'icon' => 'book-open', 'url' => route('help.guide')],
    ];
    $currentProjectId = app(\App\Support\ProjectContext::class)->id();
    if ($currentProjectId) {
        array_unshift($paletteCommands, ['type' => __('ui.palette_go'), 'title' => __('ui.palette_project_dashboard'), 'icon' => 'abstract-26', 'url' => route('projects.dashboard', $currentProjectId)]);
    }
    foreach (['BusinessNeed', 'BusinessObjective', 'StakeholderNeed', 'FunctionalRequirement', 'NonFunctionalRequirement', 'Feature', 'ChangeRequest', 'Risk', 'Stakeholder'] as $entity) {
        if (! array_key_exists($entity, \App\Support\CrudEntityRegistry::all()) || ! entity_can($entity, 'create')) {
            continue;
        }
        $label = \Illuminate\Support\Str::singular((string) (\App\Support\CrudEntityRegistry::all()[$entity]['nav_label'] ?? $entity));
        $paletteCommands[] = ['type' => __('ui.palette_create'), 'title' => __('ui.palette_new', ['entity' => $label]), 'icon' => 'plus', 'url' => model_route($entity, 'create'), 'modal' => model_modal_path($entity, 'create')];
    }
@endphp
<div class="ba-palette" data-command-palette hidden
     data-search-url="{{ route('quick.search') }}"
     role="dialog" aria-modal="true" aria-labelledby="ba-palette-label">
    <div class="ba-palette__backdrop" data-palette-close></div>
    <div class="ba-palette__panel">
        <label id="ba-palette-label" for="ba-palette-input" class="sr-only">{{ __('ui.palette_label') }}</label>
        <div class="ba-palette__search">
            <i class="ki-filled ki-magnifier" aria-hidden="true"></i>
            <input id="ba-palette-input" type="text" autocomplete="off" spellcheck="false"
                   placeholder="{{ __('ui.palette_placeholder') }}"
                   role="combobox" aria-expanded="true" aria-controls="ba-palette-list" aria-autocomplete="list">
            <kbd>Esc</kbd>
        </div>
        <ul id="ba-palette-list" class="ba-palette__list" role="listbox"></ul>
        <div class="ba-palette__foot">
            <span><kbd>↑</kbd><kbd>↓</kbd> {{ __('ui.palette_hint_move') }}</span>
            <span><kbd>Enter</kbd> {{ __('ui.palette_hint_open') }}</span>
            <span>{{ __('ui.palette_hint_codes') }}</span>
        </div>
    </div>
    <script type="application/json" data-palette-commands>@json($paletteCommands)</script>
    <script type="application/json" data-palette-i18n>@json(['empty' => __('ui.palette_empty'), 'searching' => __('ui.palette_searching')])</script>
</div>
@endauth
