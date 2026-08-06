@php
    use App\Services\C4ArchitectureNormalizer;

    $normalizer = app(C4ArchitectureNormalizer::class);
    $elementRows = is_array($elements ?? null) ? $elements : [];
    $relationshipRows = is_array($relationships ?? null) ? $relationships : [];
    $features = is_array($features ?? null) ? $features : [];
    $editable = $editable ?? true;
    $autoRender = $autoRender ?? ! $editable;
    $focusSystemKey = $focusSystemKey ?? null;
    $focusContainerKey = $focusContainerKey ?? null;
    $exportDslUrl = $exportDslUrl ?? null;
    $exportJsonUrl = $exportJsonUrl ?? null;

    $byKey = [];
    foreach ($elementRows as $row) {
        if (! empty($row['key'])) {
            $byKey[$row['key']] = $row;
        }
    }

    $byParent = [];
    foreach ($elementRows as $index => $row) {
        $parent = (string) ($row['parent_key'] ?? '');
        // Only nest under a real parent that exists; orphans render at root.
        if ($parent !== '' && ! isset($byKey[$parent])) {
            $parent = '';
        }
        // Containers/components nest under their parents; systems/persons under groups.
        // Flat root list only includes rows whose parent is empty OR whose parent is
        // already rendered as an ancestor via recursion (so skip non-root here).
        $byParent[$parent][] = ['row' => $row, 'index' => $index];
    }

    $rootItems = $byParent[''] ?? [];

    $kindLabels = [
        'person' => __('ui.c4_kind_person'),
        'system' => __('ui.c4_kind_system'),
        'container' => __('ui.c4_kind_container'),
        'component' => __('ui.c4_kind_component'),
        'group' => __('ui.c4_kind_group'),
    ];
    $shapeOptions = [
        '' => __('ui.c4_shape_default'),
        'rounded' => __('ui.c4_shape_rounded'),
        'eightSided' => __('ui.c4_shape_eight_sided'),
    ];
    $formOptions = [
        'box' => __('ui.c4_form_box'),
        'database' => __('ui.c4_form_database'),
        'queue' => __('ui.c4_form_queue'),
    ];
    $directionOptions = [
        'rel' => __('ui.c4_rel_dir_default'),
        'up' => __('ui.c4_rel_dir_up'),
        'down' => __('ui.c4_rel_dir_down'),
        'left' => __('ui.c4_rel_dir_left'),
        'right' => __('ui.c4_rel_dir_right'),
        'back' => __('ui.c4_rel_dir_back'),
        'bi' => __('ui.c4_rel_dir_bi'),
    ];
    $layout = app(C4ArchitectureNormalizer::class)->normalizeLayout(
        is_array($layout ?? null) ? $layout : []
    );

    $treeHtml = view('pages.architectures.partials.element-row', [
        'index' => '__INDEX__',
        'row' => [
            'key' => '',
            'kind' => 'system',
            'name' => '',
            'description' => '',
            'technology' => '',
            'parent_key' => '',
            'external' => false,
            'form' => 'box',
            'feature_ids' => [],
            'style' => [],
        ],
        'editable' => true,
        'kindLabels' => $kindLabels,
        'shapeOptions' => $shapeOptions,
        'formOptions' => $formOptions,
        'features' => $features,
        'depth' => 0,
        'parentName' => null,
        'byParent' => [],
        'byKey' => [],
    ])->render();
@endphp

@once
    <style>
        .bassist-mermaid { background: #ffffff; min-height: 4rem; }
        .bassist-mermaid svg { max-width: 100%; height: auto; }
        .c4-style-panel { display: none; }
        .c4-style-panel.is-open { display: block; }
        .c4-tree-row.is-collapsed-parent > [data-element-children] { display: none !important; }

        /* Hard spacing — do not rely on utility classes alone (theme can reset margins). */
        [data-architecture-c4-editor] {
            display: flex;
            flex-direction: column;
            gap: 1.25rem; /* gap-5 — consistent card spacing */
        }
        [data-architecture-c4-editor] [data-elements-tree] {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        [data-architecture-c4-editor] [data-element-children] {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 0.75rem;
            padding: 0.75rem 0.75rem 0.75rem 0.85rem;
            border-left: 3px solid color-mix(in srgb, var(--border, #e5e7eb) 85%, #64748b);
            border-radius: 0 0.5rem 0.5rem 0;
            background: color-mix(in srgb, var(--muted, #f1f5f9) 35%, transparent);
        }
        [data-architecture-c4-editor] [data-element-children].is-hidden,
        [data-architecture-c4-editor] [data-element-children][hidden] {
            display: none !important;
        }

        /* Root = one clear panel; nested = no second/third card frame. */
        [data-architecture-c4-editor] .c4-tree-row--root {
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 0.5rem;
            background: var(--background, #fff);
            padding: 0.85rem 1rem;
        }
        [data-architecture-c4-editor] .c4-tree-row--nested {
            border: 0;
            border-radius: 0.375rem;
            background: transparent;
            padding: 0.35rem 0.15rem;
        }
        [data-architecture-c4-editor] .c4-tree-row--nested + .c4-tree-row--nested {
            border-top: 1px dashed color-mix(in srgb, var(--border, #e5e7eb) 80%, transparent);
            padding-top: 0.65rem;
            margin-top: 0.1rem;
        }
    </style>
@endonce

<div
    data-architecture-c4-editor
    @if ($autoRender) data-auto-render="1" @endif
    data-editable="{{ $editable ? '1' : '0' }}"
    data-focus-system="{{ $focusSystemKey ?? '' }}"
    data-focus-container="{{ $focusContainerKey ?? '' }}"
    data-i18n-move-to-group="{{ __('ui.c4_move_to_group') }}"
    data-i18n-no-group="{{ __('ui.c4_no_group') }}"
    data-i18n-parent="{{ __('ui.c4_parent') }}"
    data-i18n-open-system="{{ __('ui.c4_open_system') }}"
    data-i18n-open-container="{{ __('ui.c4_open_container') }}"
    data-i18n-level-help-context="{{ __('ui.c4_level_help_context') }}"
    data-i18n-level-help-container="{{ __('ui.c4_level_help_container') }}"
    data-i18n-level-help-component="{{ __('ui.c4_level_help_component') }}"
    data-i18n-preview-empty="{{ __('ui.c4_preview_empty') }}"
    data-i18n-preview-empty-container="{{ __('ui.c4_preview_empty_container') }}"
    data-i18n-preview-empty-component="{{ __('ui.c4_preview_empty_component') }}"
    data-i18n-preview-need-system="{{ __('ui.c4_preview_need_system') }}"
    data-i18n-preview-need-container="{{ __('ui.c4_preview_need_container') }}"
    data-i18n-preview-render-error="{{ __('ui.c4_preview_render_error') }}"
    data-i18n-form-help="{{ __('ui.c4_form_help') }}"
    data-i18n-show-source="{{ __('ui.c4_show_source') }}"
    data-i18n-hide-source="{{ __('ui.c4_hide_source') }}"
    class="space-y-5"
>
    <x-card :title="__('ui.c4_systems_users')">
        <x-slot:toolbar>
            @if ($editable)
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary" data-add-kind="system">{{ __('ui.c4_add_system') }}</button>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-add-kind="system" data-external="1">{{ __('ui.c4_add_external') }}</button>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-add-kind="person">{{ __('ui.c4_add_person') }}</button>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-add-kind="group">{{ __('ui.c4_add_group') }}</button>
                </div>
            @endif
        </x-slot:toolbar>

        <p class="text-sm text-muted-foreground mb-4">{{ __('ui.c4_systems_users_help') }}</p>
        <p class="text-xs text-muted-foreground mb-4">{{ __('ui.c4_form_help') }}</p>
        <p class="text-xs text-muted-foreground mb-4">{{ __('ui.c4_order_help') }}</p>

        <div data-elements-tree>
            @forelse ($rootItems as $item)
                @php
                    $row = $item['row'];
                    $parentKey = $row['parent_key'] ?? null;
                    $parentName = ($parentKey && isset($byKey[$parentKey])) ? ($byKey[$parentKey]['name'] ?? $parentKey) : null;
                @endphp
                    @include('pages.architectures.partials.element-row', [
                        'index' => $item['index'],
                        'row' => $row,
                        'editable' => $editable,
                        'kindLabels' => $kindLabels,
                        'shapeOptions' => $shapeOptions,
                        'formOptions' => $formOptions,
                        'features' => $features,
                        'depth' => 0,
                        'parentName' => $parentName,
                        'byParent' => $byParent,
                        'byKey' => $byKey,
                    ])
            @empty
                @if ($editable)
                    <p class="text-sm text-muted-foreground" data-empty-elements>{{ __('ui.c4_empty_elements') }}</p>
                @endif
            @endforelse
        </div>

        <template data-element-row-template>{!! $treeHtml !!}</template>
    </x-card>

    <x-card :title="__('ui.c4_relationships')">
        <x-slot:toolbar>
            @if ($editable)
                <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-add-relationship>{{ __('ui.c4_add_relationship') }}</button>
            @endif
        </x-slot:toolbar>

        <div class="overflow-x-auto border border-border rounded-lg">
            <table class="kt-table table-auto w-full" data-relationships-table>
                <thead>
                    <tr>
                        <th class="min-w-36">{{ __('ui.c4_from') }}</th>
                        <th class="min-w-36">{{ __('ui.c4_to') }}</th>
                        <th class="min-w-40">{{ __('ui.c4_rel_label') }}</th>
                        <th class="min-w-32">{{ __('ui.c4_technology') }}</th>
                        <th class="min-w-32">{{ __('ui.c4_rel_direction') }}</th>
                        @if ($editable)
                            <th class="w-20">{{ __('ui.actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($relationshipRows as $index => $rel)
                        @php
                            $direction = strtolower((string) ($rel['direction'] ?? 'rel'));
                            if (! isset($directionOptions[$direction])) {
                                $direction = 'rel';
                            }
                        @endphp
                        <tr
                            data-relationship-row
                            data-from-key="{{ $rel['from_key'] ?? '' }}"
                            data-to-key="{{ $rel['to_key'] ?? '' }}"
                            data-label="{{ $rel['label'] ?? '' }}"
                            data-technology="{{ $rel['technology'] ?? '' }}"
                            data-direction="{{ $direction }}"
                        >
                            <td>
                                @if ($editable)
                                    <input type="text" class="kt-input" data-field="from_key" name="relationships[{{ $index }}][from_key]" value="{{ $rel['from_key'] ?? '' }}" list="c4-element-keys" autocomplete="off">
                                @else
                                    <span class="text-sm">{{ $rel['from_key'] ?? '' }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($editable)
                                    <input type="text" class="kt-input" data-field="to_key" name="relationships[{{ $index }}][to_key]" value="{{ $rel['to_key'] ?? '' }}" list="c4-element-keys" autocomplete="off">
                                @else
                                    <span class="text-sm">{{ $rel['to_key'] ?? '' }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($editable)
                                    <input type="text" class="kt-input" data-field="label" name="relationships[{{ $index }}][label]" value="{{ $rel['label'] ?? '' }}" placeholder="Uses" autocomplete="off">
                                @else
                                    <span class="text-sm">{{ $rel['label'] ?? '' }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($editable)
                                    <input type="text" class="kt-input" data-field="technology" name="relationships[{{ $index }}][technology]" value="{{ $rel['technology'] ?? '' }}" autocomplete="off">
                                @else
                                    <span class="text-sm">{{ $rel['technology'] ?? '' }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($editable)
                                    <select class="kt-select" data-field="direction" name="relationships[{{ $index }}][direction]">
                                        @foreach ($directionOptions as $value => $label)
                                            <option value="{{ $value }}" @selected($direction === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="text-sm">{{ $directionOptions[$direction] ?? $direction }}</span>
                                @endif
                            </td>
                            @if ($editable)
                                <td>
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-remove-relationship>{{ __('ui.delete') }}</button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        @if ($editable)
                            <tr data-relationship-row data-direction="rel">
                                <td><input type="text" class="kt-input" data-field="from_key" name="relationships[0][from_key]" list="c4-element-keys" autocomplete="off"></td>
                                <td><input type="text" class="kt-input" data-field="to_key" name="relationships[0][to_key]" list="c4-element-keys" autocomplete="off"></td>
                                <td><input type="text" class="kt-input" data-field="label" name="relationships[0][label]" placeholder="Uses" autocomplete="off"></td>
                                <td><input type="text" class="kt-input" data-field="technology" name="relationships[0][technology]" autocomplete="off"></td>
                                <td>
                                    <select class="kt-select" data-field="direction" name="relationships[0][direction]">
                                        @foreach ($directionOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-remove-relationship>{{ __('ui.delete') }}</button></td>
                            </tr>
                        @endif
                    @endforelse
                </tbody>
            </table>
        </div>
        <datalist id="c4-element-keys" data-element-keys-list></datalist>
        <p class="text-xs text-muted-foreground mt-3">{{ __('ui.c4_rel_direction_help') }}</p>
    </x-card>

    <x-card :title="__('ui.c4_preview')">
        <x-slot:toolbar>
            <div class="flex flex-wrap items-center gap-2">
                @if ($editable)
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary" data-preview-diagram>{{ __('ui.preview_diagram') }}</button>
                @endif
                <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-toggle-mermaid-source>{{ __('ui.c4_show_source') }}</button>
                @if ($exportDslUrl)
                    <a class="kt-btn kt-btn-sm kt-btn-secondary" href="{{ $exportDslUrl }}">{{ __('ui.c4_export_dsl') }}</a>
                @endif
                @if ($exportJsonUrl)
                    <a class="kt-btn kt-btn-sm kt-btn-secondary" href="{{ $exportJsonUrl }}">{{ __('ui.c4_export_json') }}</a>
                @endif
            </div>
        </x-slot:toolbar>

        <p class="text-sm text-muted-foreground mb-4">{{ __('ui.c4_preview_help') }}</p>

        <div class="flex flex-col gap-3 mb-4" data-preview-controls>
            <div class="flex flex-wrap gap-1 c4-level-tabs" role="tablist">
                <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-c4-level="context" aria-selected="true">{{ __('ui.c4_level_context') }}</button>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-c4-level="container" aria-selected="false">{{ __('ui.c4_level_container') }}</button>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-c4-level="component" aria-selected="false">{{ __('ui.c4_level_component') }}</button>
            </div>
            <p class="text-sm text-muted-foreground" data-level-help>{{ __('ui.c4_level_help_context') }}</p>
            <div class="flex flex-wrap items-end gap-3">
                <div class="hidden max-w-xs w-full sm:w-auto" data-focus-system-wrap>
                    <label class="kt-form-label mb-1.5" data-focus-system-label>{{ __('ui.c4_open_system') }}</label>
                    <select class="kt-select" data-focus-system-select aria-label="{{ __('ui.c4_open_system') }}">
                        <option value="">{{ __('ui.c4_open_system') }}</option>
                    </select>
                </div>
                <div class="hidden max-w-xs w-full sm:w-auto" data-focus-container-wrap>
                    <label class="kt-form-label mb-1.5" data-focus-container-label>{{ __('ui.c4_open_container') }}</label>
                    <select class="kt-select" data-focus-container-select aria-label="{{ __('ui.c4_open_container') }}">
                        <option value="">{{ __('ui.c4_open_container') }}</option>
                    </select>
                </div>
            </div>

            <div class="rounded-lg border border-border bg-muted/20 p-3" data-layout-controls>
                <div class="mb-2">
                    <div class="text-sm font-medium text-foreground">{{ __('ui.c4_layout') }}</div>
                    <p class="text-xs text-muted-foreground mt-0.5">{{ __('ui.c4_layout_help') }}</p>
                </div>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="max-w-[8rem] w-full" data-layout-shapes-wrap>
                        <label class="kt-form-label mb-1.5" for="c4-layout-shapes">{{ __('ui.c4_shapes_per_row') }}</label>
                        <input
                            id="c4-layout-shapes"
                            type="number"
                            min="1"
                            max="12"
                            class="kt-input"
                            data-layout-shapes-per-row
                            name="layout[shapes_per_row]"
                            value="{{ $layout['shapes_per_row'] }}"
                            @disabled(! $editable)
                        >
                    </div>
                    <div class="max-w-[8rem] w-full" data-layout-boundaries-wrap>
                        <label class="kt-form-label mb-1.5" for="c4-layout-boundaries">{{ __('ui.c4_boundaries_per_row') }}</label>
                        <input
                            id="c4-layout-boundaries"
                            type="number"
                            min="1"
                            max="12"
                            class="kt-input"
                            data-layout-boundaries-per-row
                            name="layout[boundaries_per_row]"
                            value="{{ $layout['boundaries_per_row'] }}"
                            @disabled(! $editable)
                        >
                    </div>
                </div>
            </div>
        </div>

        <div class="mermaid bassist-mermaid border border-border rounded-lg p-4 overflow-auto" data-mermaid-preview data-level="context"></div>
        <pre class="hidden text-xs text-muted-foreground whitespace-pre-wrap mt-3 border border-border rounded-lg p-3 overflow-auto max-h-64" data-mermaid-source>{{ $mermaidContext ?? "C4Context\n" }}</pre>
    </x-card>
</div>
