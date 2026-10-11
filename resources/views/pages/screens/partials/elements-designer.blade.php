{{--
    The screen builder: a tree of element rows (containers hold their children, like the C4
    editor), shorthand typing, and an always-on preview of what is in the form right now.
    The tree is built in the browser (public/js/screen-designer.js) and written back into
    hidden elements[i][...] inputs, so saving needs nothing special. Nothing is saved by
    the preview.
--}}
@php
    $requirements = $projectId > 0
        ? \App\Models\FunctionalRequirement::query()
            ->where('project_id', $projectId)
            ->orderBy('number')
            ->get(['id', 'number', 'title'])
            ->map(fn ($fr) => ['id' => $fr->id, 'label' => $fr->code.' — '.$fr->title])
            ->values()
            ->all()
        : [];
    $kinds = \App\Support\ScreenElementKind::selectOptions();
    $initial = collect($elements)
        ->filter(fn ($row) => is_array($row))
        ->map(fn ($row) => [
            'id' => $row['id'] ?? null,
            'key' => (string) ($row['key'] ?? ($row['id'] ?? '')),
            'parent_key' => $row['parent_key'] ?? null,
            'row' => $row['row'] ?? null,
            'kind' => $row['kind'] ?? 'label',
            'label' => $row['label'] ?? '',
            'functional_requirement_id' => $row['functional_requirement_id'] ?? null,
        ])
        ->values()
        ->all();
    $designerId = 'screen-designer-'.uniqid();
@endphp

<link rel="stylesheet" href="{{ asset('css/salt-wireframe.css').'?v='.@filemtime(public_path('css/salt-wireframe.css')) }}">
<link rel="stylesheet" href="{{ asset('css/screen-designer.css').'?v='.@filemtime(public_path('css/screen-designer.css')) }}">

<div
    id="{{ $designerId }}"
    data-screen-builder
    data-preview-url="{{ $previewUrl }}"
    data-initial="{{ json_encode($initial, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
    data-requirements="{{ json_encode($requirements, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
    data-kinds="{{ json_encode($kinds, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
    data-empty-text="{{ __('ui.screen_builder_empty') }}"
    data-text-preview-ok="{{ __('ui.screen_preview_ok') }}"
    data-text-salt-edited="{{ __('ui.screen_salt_edited') }}"
    data-text-salt-nothing="{{ __('ui.screen_salt_nothing') }}"
>
    {{-- Tells the server the rows were submitted, so removing every row removes them all. --}}
    <input type="hidden" name="elements[_present]" value="1">
    <div data-hidden></div>

    <p class="text-sm text-muted-foreground">{{ __('ui.screen_builder_help') }}</p>

    <div class="sb-layout">
        <div class="sb-main">
            {{-- Add things to the end of the screen: pick from a list, pick a ready-made pattern, or type / paste. --}}
            <div class="sb-card sb-add-block">
                <div class="flex flex-wrap items-center gap-2">
                    <select class="kt-select" data-palette aria-label="{{ __('ui.screen_builder_add_element') }}" style="max-width: 14rem;">
                        <option value="">+ {{ __('ui.screen_builder_add_element') }}</option>
                        <optgroup label="{{ __('ui.screen_builder_group_elements') }}">
                            @foreach (['label', 'input', 'button', 'checkbox', 'radio', 'select', 'separator'] as $paletteKind)
                                <option value="{{ $paletteKind }}">{{ $kinds[$paletteKind] ?? $paletteKind }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="{{ __('ui.screen_builder_group_containers') }}">
                            @foreach (['panel', 'columns', 'column', 'table'] as $paletteKind)
                                <option value="{{ $paletteKind }}">{{ $kinds[$paletteKind] ?? $paletteKind }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    <select class="kt-select" data-pattern aria-label="{{ __('ui.screen_builder_pattern') }}" style="max-width: 14rem;">
                        <option value="">{{ __('ui.screen_builder_pattern') }}</option>
                        <option value="login">{{ __('ui.screen_pattern_login') }}</option>
                        <option value="form">{{ __('ui.screen_pattern_form') }}</option>
                        <option value="list">{{ __('ui.screen_pattern_list') }}</option>
                        <option value="columns">{{ __('ui.screen_pattern_columns') }}</option>
                        <option value="detail">{{ __('ui.screen_pattern_detail') }}</option>
                    </select>
                </div>

                <div class="flex gap-2 items-start">
                    <textarea class="kt-input grow sb-quick" rows="1" data-quick spellcheck="false" aria-label="{{ __('ui.screen_builder_quick') }}" placeholder="{{ __('ui.screen_builder_quick_placeholder') }}" style="font-family: ui-monospace, monospace; resize: vertical;"></textarea>
                    <button type="button" class="kt-btn kt-btn-primary" data-quick-add>{{ __('ui.screen_builder_add') }}</button>
                </div>
                <p class="text-xs text-muted-foreground">{{ __('ui.screen_builder_quick_help') }}</p>

                <details class="sb-cheat mt-2">
                    <summary class="text-xs cursor-pointer text-muted-foreground">{{ __('ui.screen_builder_shortcuts') }}</summary>
                    <table class="text-xs mt-2">
                        <tbody>
                            <tr><td><code>Some text</code></td><td>{{ __('ui.screen_short_label') }}</td></tr>
                            <tr><td><code>[Save]</code></td><td>{{ __('ui.screen_short_button') }}</td></tr>
                            <tr><td><code>[Save] [Cancel]</code></td><td>{{ __('ui.screen_short_buttons') }}</td></tr>
                            <tr><td><code>"Enter name"</code></td><td>{{ __('ui.screen_short_input') }}</td></tr>
                            <tr><td><code>Name | "Enter name"</code></td><td>{{ __('ui.screen_short_pair') }}</td></tr>
                            <tr><td><code>[ ] Active</code> &nbsp; <code>() Yes</code> &nbsp; <code>^Branch^</code></td><td>{{ __('ui.screen_short_choices') }}</td></tr>
                            <tr><td><code>--</code></td><td>{{ __('ui.screen_short_rule') }}</td></tr>
                            <tr><td><code>panel Title</code> &nbsp; <code>columns</code> &nbsp; <code>column</code> &nbsp; <code>table</code></td><td>{{ __('ui.screen_short_containers') }}</td></tr>
                            <tr><td><code>&nbsp;&nbsp;indented lines</code></td><td>{{ __('ui.screen_short_indent') }}</td></tr>
                            <tr><td><code>| A | B |</code></td><td>{{ __('ui.screen_short_table') }}</td></tr>
                            <tr><td><kbd>Enter</kbd> <kbd>Tab</kbd> <kbd>Shift+Tab</kbd> <kbd>Alt+↑↓</kbd> <kbd>Backspace</kbd></td><td>{{ __('ui.screen_short_keys') }}</td></tr>
                        </tbody>
                    </table>
                </details>
            </div>

            <div class="sb-tree" data-tree></div>

            {{-- Search and filter: closed by default, under the tree. Narrows what the tree shows; hidden rows are still saved. No name= so none of it is submitted. --}}
            <details class="sb-card sb-filter-wrap">
                <summary class="sb-filter-summary">
                    <span>{{ __('ui.screen_filter_summary') }}</span>
                    <span class="text-sm text-muted-foreground" data-filter-count></span>
                </summary>
                <div class="sb-filter">
                <div class="grow max-w-xs">
                    <label class="kt-form-label mb-1.5" for="{{ $designerId }}-ft">{{ __('ui.search') }}</label>
                    <input type="search" id="{{ $designerId }}-ft" class="kt-input" data-filter-text placeholder="{{ __('ui.screen_filter_label') }}" autocomplete="off">
                </div>
                <div class="max-w-xs">
                    <label class="kt-form-label mb-1.5" for="{{ $designerId }}-fk">{{ __('ui.screen_element_kind') }}</label>
                    <select id="{{ $designerId }}-fk" class="kt-select" data-filter-kind>
                        <option value="">{{ __('ui.all') }}</option>
                        @foreach ($kinds as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="kt-btn kt-btn-outline" data-filter-clear>{{ __('ui.screen_filter_clear') }}</button>
            </div>
            </details>
        </div>

        <div class="sb-side">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-foreground">{{ __('ui.screen_preview') }}</h3>
                <span class="text-xs text-muted-foreground ms-auto" data-preview-status>{{ __('ui.screen_preview_help') }}</span>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-preview-popup>{{ __('ui.screen_preview_popup') }}</button>
            </div>
            <div class="sb-preview-box" data-preview></div>

            {{-- The Salt text: closed by default. Open it to read, edit, copy or apply it back to the elements. --}}
            <details class="sb-card sb-salt">
                <summary class="sb-salt-summary">{{ __('ui.screen_salt_title') }}</summary>
                <div class="sb-salt-body">
                    <p class="text-xs text-muted-foreground">{{ __('ui.screen_salt_help') }}</p>
                    <textarea class="kt-input sb-salt-text" rows="24" wrap="off" data-salt spellcheck="false" aria-label="{{ __('ui.screen_salt_title') }}"></textarea>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="kt-btn kt-btn-primary" data-salt-apply>{{ __('ui.screen_salt_apply') }}</button>
                        <button type="button" class="kt-btn kt-btn-outline" data-salt-reset>{{ __('ui.screen_salt_reset') }}</button>
                        <button type="button" class="kt-btn kt-btn-ghost" data-salt-copy>{{ __('ui.screen_salt_copy') }}</button>
                    </div>
                </div>
            </details>
        </div>
    </div>
</div>

<script>
    (function () {
        var root = document.getElementById(@json($designerId));

        // The page may inject scripts after this one runs, so load in order and start when ready.
        function load(urls, done) {
            if (!urls.length) { done(); return; }
            var url = urls.shift();
            var tag = document.createElement('script');
            tag.src = url;
            tag.onload = function () { load(urls, done); };
            tag.onerror = function () { root.querySelector('[data-preview]').textContent = 'Could not load ' + url; };
            document.head.appendChild(tag);
        }

        var needed = [];
        if (!window.SaltWireframe) needed.push(@json(asset('js/salt-wireframe.js').'?v='.@filemtime(public_path('js/salt-wireframe.js'))));
        if (!window.ScreenDesigner) needed.push(@json(asset('js/screen-designer.js').'?v='.@filemtime(public_path('js/screen-designer.js'))));
        load(needed, function () { window.ScreenDesigner.init(root); });
    })();
</script>
