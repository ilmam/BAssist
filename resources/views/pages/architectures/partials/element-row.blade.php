@php
    $kind = strtolower((string) ($row['kind'] ?? 'system'));
    if (! isset($kindLabels[$kind])) {
        $kind = 'system';
    }
    $style = is_array($row['style'] ?? null) ? $row['style'] : [];
    $featureIds = is_array($row['feature_ids'] ?? null) ? $row['feature_ids'] : [];
    $external = ! empty($row['external']);
    $form = strtolower((string) ($row['form'] ?? 'box'));
    if (! in_array($form, ['box', 'database', 'queue'], true)) {
        $form = 'box';
    }
    $formOptions = is_array($formOptions ?? null) ? $formOptions : [
        'box' => __('ui.c4_form_box'),
        'database' => __('ui.c4_form_database'),
        'queue' => __('ui.c4_form_queue'),
    ];
    $indexAttr = $index === '__INDEX__' ? '__INDEX__' : (int) $index;
    $depth = max(0, (int) ($depth ?? 0));
    $parentName = $parentName ?? null;
    $byParent = is_array($byParent ?? null) ? $byParent : [];
    $byKey = is_array($byKey ?? null) ? $byKey : [];
    $canCollapse = in_array($kind, ['group', 'system', 'container'], true) && ! ($kind === 'system' && $external);
    $canHaveChildren = $canCollapse;
    $canMoveToGroup = $editable && in_array($kind, ['system', 'person'], true);
    $rowKey = (string) ($row['key'] ?? '');
    $childItems = ($rowKey !== '' && isset($byParent[$rowKey])) ? $byParent[$rowKey] : [];
    // Top-level = panel; nested = soft row (no second card frame).
    $surfaceClass = $depth === 0 ? 'c4-tree-row--root' : 'c4-tree-row--nested';
@endphp

<div
    class="c4-tree-row {{ $surfaceClass }}"
    data-element-row
    data-kind="{{ $kind }}"
    data-key="{{ $rowKey }}"
    data-name="{{ $row['name'] ?? '' }}"
    data-description="{{ $row['description'] ?? '' }}"
    data-technology="{{ $row['technology'] ?? '' }}"
    data-parent-key="{{ $row['parent_key'] ?? '' }}"
    data-external="{{ $external ? '1' : '0' }}"
    data-form="{{ $form }}"
    data-bg-color="{{ $style['bg_color'] ?? '' }}"
    data-font-color="{{ $style['font_color'] ?? '' }}"
    data-border-color="{{ $style['border_color'] ?? '' }}"
    data-depth="{{ $depth }}"
>
    <div class="flex flex-wrap items-end gap-2" data-element-header>
        @if ($editable)
            <input type="hidden" data-field="key" name="elements[{{ $indexAttr }}][key]" value="{{ $rowKey }}">
            <input type="hidden" data-field="kind" name="elements[{{ $indexAttr }}][kind]" value="{{ $kind }}">
            <input type="hidden" data-field="parent_key" name="elements[{{ $indexAttr }}][parent_key]" value="{{ $row['parent_key'] ?? '' }}">
            <input type="hidden" data-field="external" name="elements[{{ $indexAttr }}][external]" value="{{ $external ? '1' : '0' }}">

            @if ($canCollapse)
                <button
                    type="button"
                    class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0"
                    data-toggle-collapse
                    title="{{ __('ui.c4_collapse') }}"
                    aria-expanded="true"
                >
                    <i class="ki-filled ki-down text-xs" data-collapse-icon></i>
                </button>
            @endif

            <div class="grow min-w-[160px]">
                <div class="flex flex-wrap items-center gap-1.5 mb-1">
                    <label class="kt-form-label mb-0 text-xs text-muted-foreground">{{ $kindLabels[$kind] }}@if ($external) ({{ __('ui.c4_external') }})@endif</label>
                    @if ($parentName && $depth === 0)
                        <span class="text-[11px] text-muted-foreground/80" data-parent-badge>{{ __('ui.c4_parent') }}: {{ $parentName }}</span>
                    @endif
                </div>
                <input type="text" class="kt-input" data-field="name" name="elements[{{ $indexAttr }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="{{ __('ui.c4_name') }}" autocomplete="off">
            </div>
            <div class="grow min-w-[160px]">
                <label class="kt-form-label mb-1 text-xs text-muted-foreground">{{ __('ui.description') }}</label>
                <input type="text" class="kt-input" data-field="description" name="elements[{{ $indexAttr }}][description]" value="{{ $row['description'] ?? '' }}" autocomplete="off">
            </div>
            @if (in_array($kind, ['system', 'container', 'component'], true))
                <div class="min-w-[120px]" data-form-wrap>
                    <label class="kt-form-label mb-1 text-xs text-muted-foreground">{{ __('ui.c4_form') }}</label>
                    <select class="kt-select" data-field="form" name="elements[{{ $indexAttr }}][form]">
                        @foreach ($formOptions as $value => $label)
                            <option value="{{ $value }}" @selected($form === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" data-field="form" name="elements[{{ $indexAttr }}][form]" value="box">
            @endif
            @if (in_array($kind, ['container', 'component'], true))
                <div class="min-w-[120px]" data-technology-wrap>
                    <label class="kt-form-label mb-1 text-xs text-muted-foreground">{{ __('ui.c4_technology') }}</label>
                    <input type="text" class="kt-input" data-field="technology" name="elements[{{ $indexAttr }}][technology]" value="{{ $row['technology'] ?? '' }}" autocomplete="off">
                </div>
            @else
                <input type="hidden" data-field="technology" name="elements[{{ $indexAttr }}][technology]" value="{{ $row['technology'] ?? '' }}">
            @endif
            @if ($canMoveToGroup)
                <div class="min-w-[140px] max-w-[12rem]" data-move-to-group-wrap>
                    <label class="kt-form-label mb-1 text-xs text-muted-foreground">{{ __('ui.c4_move_to_group') }}</label>
                    <select class="kt-select" data-move-to-group>
                        <option value="">{{ __('ui.c4_no_group') }}</option>
                        @foreach ($byKey as $groupKey => $groupRow)
                            @if (($groupRow['kind'] ?? '') === 'group')
                                <option value="{{ $groupKey }}" @selected(($row['parent_key'] ?? '') === $groupKey)>{{ $groupRow['name'] ?? $groupKey }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            @endif
        @else
            @if ($canCollapse)
                <button
                    type="button"
                    class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0 self-center"
                    data-toggle-collapse
                    title="{{ __('ui.c4_collapse') }}"
                    aria-expanded="true"
                >
                    <i class="ki-filled ki-down text-xs" data-collapse-icon></i>
                </button>
            @endif
            <div class="grow">
                <div class="flex flex-wrap items-center gap-1.5 mb-0.5">
                    <span class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ $kindLabels[$kind] }}@if ($external) · {{ __('ui.c4_external') }}@endif</span>
                    @if ($rowKey !== '')
                        <span class="text-[11px] text-muted-foreground/70">{{ $rowKey }}</span>
                    @endif
                </div>
                <div class="font-medium text-foreground">{{ $row['name'] ?? '' }}</div>
                @if (! empty($row['description']))
                    <div class="text-sm text-muted-foreground">{{ $row['description'] }}</div>
                @endif
                @if (! empty($row['technology']))
                    <div class="text-xs text-muted-foreground">{{ $row['technology'] }}</div>
                @endif
                @if (in_array($kind, ['system', 'container', 'component'], true) && $form !== 'box')
                    <div class="text-xs text-muted-foreground">{{ $formOptions[$form] ?? $form }}</div>
                @endif
            </div>
            <div class="flex flex-wrap gap-1 ms-auto pb-0.5" data-element-actions>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-toggle-style>{{ __('ui.c4_style') }}</button>
            </div>
        @endif

        @if ($editable)
            <div class="flex flex-wrap gap-1 ms-auto pb-0.5" data-element-actions>
                @if ($kind === 'group')
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-add-child="system">{{ __('ui.c4_add_system') }}</button>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-add-child="person">{{ __('ui.c4_add_person') }}</button>
                @endif
                @if ($kind === 'system' && ! $external)
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-add-child="container">{{ __('ui.c4_add_container') }}</button>
                @endif
                @if ($kind === 'container')
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-add-child="component">{{ __('ui.c4_add_component') }}</button>
                @endif
                <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-move-element="up" title="{{ __('ui.c4_move_up') }}">↑</button>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-move-element="down" title="{{ __('ui.c4_move_down') }}">↓</button>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-toggle-style>{{ __('ui.c4_style') }}</button>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-remove-element>{{ __('ui.delete') }}</button>
            </div>
        @endif
    </div>

    <div class="c4-style-panel mt-3 pt-3 border-t border-dashed border-border" data-style-panel>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
            @if ($editable)
                <div>
                    <label class="kt-form-label mb-1 text-xs text-muted-foreground">{{ __('ui.c4_bg_color') }}</label>
                    <input type="text" class="kt-input" data-field="bg_color" name="elements[{{ $indexAttr }}][bg_color]" value="{{ $style['bg_color'] ?? '' }}" placeholder="#1168bd" autocomplete="off">
                </div>
                <div>
                    <label class="kt-form-label mb-1 text-xs text-muted-foreground">{{ __('ui.c4_font_color') }}</label>
                    <input type="text" class="kt-input" data-field="font_color" name="elements[{{ $indexAttr }}][font_color]" value="{{ $style['font_color'] ?? '' }}" placeholder="#ffffff" autocomplete="off">
                </div>
                <div>
                    <label class="kt-form-label mb-1 text-xs text-muted-foreground">{{ __('ui.c4_border_color') }}</label>
                    <input type="text" class="kt-input" data-field="border_color" name="elements[{{ $indexAttr }}][border_color]" value="{{ $style['border_color'] ?? '' }}" autocomplete="off">
                </div>
                <div class="md:col-span-4">
                    <label class="kt-form-label mb-1 text-xs text-muted-foreground">{{ __('ui.c4_features') }}</label>
                    <select class="kt-select" data-field="feature_ids" name="elements[{{ $indexAttr }}][feature_ids][]" multiple size="3">
                        @foreach ($features as $featureId => $featureLabel)
                            <option value="{{ $featureId }}" @selected(in_array((int) $featureId, array_map('intval', $featureIds), true))>{{ $featureLabel }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="text-sm md:col-span-4 text-muted-foreground">
                    @if (! empty($style['bg_color']) || ! empty($style['font_color']) || ! empty($style['border_color']))
                        {{ __('ui.c4_style') }}:
                        {{ $style['bg_color'] ?? '—' }} /
                        {{ $style['font_color'] ?? '—' }} /
                        {{ $style['border_color'] ?? '—' }}
                    @endif
                    @if ($featureIds !== [])
                        <div class="mt-1">{{ __('ui.c4_features') }}: {{ implode(', ', $featureIds) }}</div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div
        class="c4-tree-children{{ $canHaveChildren ? '' : ' is-hidden' }}"
        data-element-children
        @if (! $canHaveChildren) hidden @endif
    >
        @foreach ($childItems as $childItem)
            @php
                $childRow = $childItem['row'];
                $childParentKey = $childRow['parent_key'] ?? null;
                $childParentName = ($childParentKey && isset($byKey[$childParentKey]))
                    ? ($byKey[$childParentKey]['name'] ?? $childParentKey)
                    : null;
            @endphp
            @include('pages.architectures.partials.element-row', [
                'index' => $childItem['index'],
                'row' => $childRow,
                'editable' => $editable,
                'kindLabels' => $kindLabels,
                'shapeOptions' => $shapeOptions,
                'formOptions' => $formOptions,
                'features' => $features,
                'depth' => $depth + 1,
                'parentName' => $childParentName,
                'byParent' => $byParent,
                'byKey' => $byKey,
            ])
        @endforeach
    </div>
</div>
