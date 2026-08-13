@php
    use App\Services\SwimlaneMermaidGenerator;

    $elementRows = is_array($elements ?? null) ? $elements : [];
    if ($elementRows === []) {
        $elementRows = [['id' => null, 'lane' => '', 'lane_color' => null, 'element_color' => null, 'from' => '', 'type' => 'process', 'label' => '', 'line_title' => '', 'code' => '', 'stakeholder_need_id' => null]];
    }

    $editable = $editable ?? true;
    $autoRender = $autoRender ?? ! $editable;
    $showTitleField = $showTitleField ?? false;
    $flowTitle = $flowTitle ?? '';
    $showDiagram = $showDiagram ?? true;
    $direction = strtoupper((string) ($direction ?? 'TB')) === 'LR' ? 'LR' : 'TB';
    $colorMode = \App\Services\SwimlaneMermaidGenerator::normalizeColorMode($colorMode ?? null);
    $stakeholderNeedOptions = is_array($stakeholderNeedOptions ?? null) ? $stakeholderNeedOptions : [];
    $stakeholderNeedOptionsUrl = $stakeholderNeedOptionsUrl ?? route('swimlane_flows.stakeholder-need-options');
    $needLabels = collect($stakeholderNeedOptions)->mapWithKeys(
        fn (array $opt) => [((string) ($opt['value'] ?? '')) => ($opt['label'] ?? '')]
    )->all();

    $typeOptions = [
        'start' => __('ui.element_type_start'),
        'process' => __('ui.element_type_process'),
        'decision' => __('ui.element_type_decision'),
        'end' => __('ui.element_type_end'),
    ];

    $laneColorPalette = SwimlaneMermaidGenerator::LANE_COLORS;
    $elementColorPalette = SwimlaneMermaidGenerator::ELEMENT_COLORS;
    $laneColorOptions = ['' => __('ui.element_lane_color_none')];
    $elementColorOptions = ['' => __('ui.element_color_none')];
    foreach (array_keys($laneColorPalette) as $key) {
        $laneColorOptions[$key] = __('ui.element_lane_color_'.$key);
    }
    foreach (array_keys($elementColorPalette) as $key) {
        $elementColorOptions[$key] = __('ui.element_color_'.$key);
    }
    // Unique per editor instance (full-page + modal) so list= targets stay local.
    $laneDatalistId = 'swimlane-lane-names-'.uniqid();
    $labelDatalistId = 'swimlane-label-names-'.uniqid();
@endphp

@once
    <style>
        .bassist-mermaid {
            background: #ffffff;
        }

        /*
         * Modal diagram zoom model:
         * - Viewport (parent): overflow auto, definite flex size.
         * - Stage (wrapper): width 100% / 125% / … of viewport; zoom = that %.
         * - Host / mermaid / svg: width 100% of stage, height auto (fills wrapper).
         * Avoid max-content / transform-scale stages that collapse to 0.
         */
        [data-diagram-preview-shell] .kt-modal-body {
            overflow: hidden !important;
        }

        [data-diagram-zoom-viewport] {
            overflow: auto;
            overscroll-behavior: contain;
            min-height: 12rem;
            flex: 1 1 auto;
            min-width: 0;
            cursor: grab;
        }

        [data-diagram-zoom-viewport].is-panning {
            cursor: grabbing;
            user-select: none;
        }

        [data-diagram-zoom-stage] {
            display: block;
            box-sizing: border-box;
            width: 100%;
            max-width: none;
        }

        [data-diagram-preview-shell] [data-mermaid-modal-host] {
            display: block;
            width: 100%;
            max-width: none;
        }

        [data-diagram-preview-shell] .bassist-mermaid {
            display: block;
            margin: 0;
            width: 100%;
            max-width: none;
            overflow: visible;
        }

        [data-diagram-preview-shell] .bassist-mermaid svg {
            display: block;
            width: 100% !important;
            max-width: none !important;
            height: auto !important;
        }

        .bassist-lane-color-swatch {
            display: inline-block;
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 9999px;
            border: 1px solid rgba(15, 23, 42, 0.25);
            vertical-align: -0.05rem;
            margin-inline-end: 0.35rem;
        }

        tr[data-element-row][data-lane-fill] {
            background-color: color-mix(in srgb, var(--bassist-lane-fill) 32%, transparent);
        }

        tr[data-element-row][data-lane-fill] > td {
            background-color: transparent;
        }

        select[data-field="lane_color"][data-swatch-fill],
        select[data-field="element_color"][data-swatch-fill] {
            background-color: color-mix(in srgb, var(--bassist-swatch-fill) 55%, transparent);
        }
    </style>
@endonce

<div
    data-swimlane-flow-editor
    @if ($autoRender) data-auto-render="1" @endif
    @if ($flowTitle !== '') data-flow-title-value="{{ $flowTitle }}" @endif
    data-direction="{{ $direction }}"
    data-color-mode="{{ $colorMode }}"
    data-stakeholder-need-options-url="{{ $stakeholderNeedOptionsUrl }}"
    data-i18n-apply-success="{{ __('ui.apply_mermaid_source_success') }}"
    data-i18n-apply-error="{{ __('ui.apply_mermaid_source_error') }}"
    data-i18n-diagram-modal-title="{{ __('ui.diagram_preview_modal_title') }}"
    data-i18n-modal-size="{{ __('ui.modal_size') }}"
    data-i18n-modal-size-small="{{ __('ui.modal_size_small') }}"
    data-i18n-modal-size-medium="{{ __('ui.modal_size_medium') }}"
    data-i18n-modal-size-large="{{ __('ui.modal_size_large') }}"
    data-i18n-modal-size-fullscreen="{{ __('ui.modal_size_fullscreen') }}"
    data-i18n-modal-size-side="{{ __('ui.modal_size_side') }}"
    data-i18n-modal-backdrop="{{ __('ui.modal_backdrop_show_page') }}"
    data-i18n-modal-sheet-float="{{ __('ui.modal_sheet_float') }}"
    data-i18n-modal-sheet-push="{{ __('ui.modal_sheet_push') }}"
    data-i18n-diagram-zoom="{{ __('ui.diagram_zoom') }}"
    data-i18n-diagram-zoom-in="{{ __('ui.diagram_zoom_in') }}"
    data-i18n-diagram-zoom-out="{{ __('ui.diagram_zoom_out') }}"
    data-i18n-diagram-zoom-fit="{{ __('ui.diagram_zoom_fit') }}"
    data-i18n-diagram-zoom-reset="{{ __('ui.diagram_zoom_reset') }}"
    data-i18n-close="{{ __('ui.close') }}"
    class="space-y-5"
>
    @if ($showTitleField)
        <input type="hidden" data-flow-title value="{{ $flowTitle }}">
    @endif

    <div class="flex flex-wrap gap-4">
        <div class="max-w-xs">
            <label class="kt-form-label mb-1.5" for="direction">{{ __('ui.direction') }}</label>
            @if ($editable)
                <select id="direction" name="direction" class="kt-select">
                    <option value="TB" @selected($direction === 'TB')>{{ __('ui.direction_tb') }}</option>
                    <option value="LR" @selected($direction === 'LR')>{{ __('ui.direction_lr') }}</option>
                </select>
            @else
                <div class="text-sm" data-direction-display>{{ $direction === 'LR' ? __('ui.direction_lr') : __('ui.direction_tb') }}</div>
            @endif
        </div>

        <div class="max-w-xs">
            <label class="kt-form-label mb-1.5" for="color_mode">{{ __('ui.swimlane_color_mode') }}</label>
            @if ($editable)
                <select id="color_mode" name="color_mode" class="kt-select" data-color-mode-input>
                    <option value="both" @selected($colorMode === 'both')>{{ __('ui.swimlane_color_mode_both') }}</option>
                    <option value="lanes" @selected($colorMode === 'lanes')>{{ __('ui.swimlane_color_mode_lanes') }}</option>
                    <option value="elements" @selected($colorMode === 'elements')>{{ __('ui.swimlane_color_mode_elements') }}</option>
                </select>
            @else
                <div class="text-sm" data-color-mode-display>
                    @if ($colorMode === 'lanes')
                        {{ __('ui.swimlane_color_mode_lanes') }}
                    @elseif ($colorMode === 'elements')
                        {{ __('ui.swimlane_color_mode_elements') }}
                    @else
                        {{ __('ui.swimlane_color_mode_both') }}
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div>
        <h4 class="text-sm font-semibold text-foreground mb-3">{{ __('ui.elements') }}</h4>
        @if ($editable)
            <p class="text-xs text-muted-foreground mb-3">{{ __('ui.swimlane_order_help') }}</p>
        @endif

        <div class="overflow-x-auto border border-border rounded-lg" data-table-density="compact">
            <table class="kt-table kt-table--compact table-auto w-full" data-elements-table>
                <thead>
                    <tr>
                        @unless ($editable)
                            <th class="min-w-24">{{ __('ui.element_code') }}</th>
                        @endunless
                        <th class="min-w-36">{{ __('ui.element_lane') }}</th>
                        <th class="min-w-36">{{ __('ui.element_from') }}</th>
                        <th class="min-w-36">{{ __('ui.element_line_title') }}</th>
                        <th class="min-w-32">{{ __('ui.element_type') }}</th>
                        <th class="min-w-40">{{ __('ui.element_label') }}</th>
                        <th class="min-w-56">{{ __('ui.element_stakeholder_need') }}</th>
                        <th class="min-w-28">{{ __('ui.element_lane_color') }}</th>
                        <th class="min-w-28">{{ __('ui.element_color') }}</th>
                        @if ($editable)
                            <th class="w-28">{{ __('ui.actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($elementRows as $index => $row)
                        @php
                            $type = strtolower((string) ($row['type'] ?? 'process'));
                            if (! array_key_exists($type, $typeOptions)) {
                                $type = 'process';
                            }
                            $code = (string) ($row['code'] ?? '');
                            $stepId = isset($row['id']) && is_numeric($row['id']) ? (int) $row['id'] : null;
                            $needId = isset($row['stakeholder_need_id']) && is_numeric($row['stakeholder_need_id'])
                                ? (string) (int) $row['stakeholder_need_id']
                                : '';
                            $needLabel = $needLabels[$needId] ?? ($needId !== '' ? $needId : '');
                            $linkable = in_array($type, ['process', 'decision'], true);
                            $laneColor = strtolower(trim((string) ($row['lane_color'] ?? '')));
                            if ($laneColor !== '' && ! array_key_exists($laneColor, $laneColorPalette)) {
                                $laneColor = '';
                            }
                            $elementColor = strtolower(trim((string) ($row['element_color'] ?? '')));
                            if ($elementColor !== '' && ! array_key_exists($elementColor, $elementColorPalette)) {
                                $elementColor = '';
                            }
                            $laneColorLabel = $laneColorOptions[$laneColor] ?? __('ui.element_lane_color_none');
                            $elementColorLabel = $elementColorOptions[$elementColor] ?? __('ui.element_color_none');
                            $laneFill = ($laneColor !== '' && isset($laneColorPalette[$laneColor]))
                                ? $laneColorPalette[$laneColor]['fill']
                                : '';
                            $elementFill = ($elementColor !== '' && isset($elementColorPalette[$elementColor]))
                                ? $elementColorPalette[$elementColor]['fill']
                                : '';
                        @endphp
                        <tr
                            data-element-row
                            @if ($laneFill !== '')
                                data-lane-fill="{{ $laneColor }}"
                                style="--bassist-lane-fill: {{ $laneFill }};"
                            @endif
                        >
                            @unless ($editable)
                                <td>
                                    @if ($stepId)
                                        <input type="hidden" data-field="id" name="elements[{{ $index }}][id]" value="{{ $stepId }}">
                                    @endif
                                    <input
                                        type="text"
                                        class="kt-input bg-muted/40"
                                        data-field="code"
                                        name="elements[{{ $index }}][code]"
                                        value="{{ $code }}"
                                        readonly
                                        tabindex="-1"
                                        placeholder="{{ __('ui.element_code_placeholder') }}"
                                        autocomplete="off"
                                    >
                                </td>
                            @endunless
                            <td>
                                @if ($editable)
                                    @if ($stepId)
                                        <input type="hidden" data-field="id" name="elements[{{ $index }}][id]" value="{{ $stepId }}">
                                    @endif
                                    <input type="hidden" data-field="code" name="elements[{{ $index }}][code]" value="{{ $code }}">
                                    <input
                                        type="text"
                                        class="kt-input"
                                        data-field="lane"
                                        name="elements[{{ $index }}][lane]"
                                        value="{{ $row['lane'] ?? '' }}"
                                        list="{{ $laneDatalistId }}"
                                        placeholder="Support"
                                        autocomplete="off"
                                    >
                                @else
                                    <span class="text-sm" data-field="lane" data-value="{{ $row['lane'] ?? '' }}">{{ $row['lane'] ?? '' }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($editable)
                                    <input
                                        type="text"
                                        class="kt-input"
                                        data-field="from"
                                        name="elements[{{ $index }}][from]"
                                        value="{{ $row['from'] ?? '' }}"
                                        list="{{ $labelDatalistId }}"
                                        placeholder="Review"
                                        autocomplete="off"
                                    >
                                @else
                                    <span class="text-sm" data-field="from" data-value="{{ $row['from'] ?? '' }}">{{ $row['from'] ?? '' }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($editable)
                                    <input
                                        type="text"
                                        class="kt-input"
                                        data-field="line_title"
                                        name="elements[{{ $index }}][line_title]"
                                        value="{{ $row['line_title'] ?? '' }}"
                                        placeholder="Yes"
                                        autocomplete="off"
                                    >
                                @else
                                    <span class="text-sm" data-field="line_title" data-value="{{ $row['line_title'] ?? '' }}">{{ $row['line_title'] ?? '' }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($editable)
                                    <select class="kt-select" data-field="type" name="elements[{{ $index }}][type]">
                                        @foreach ($typeOptions as $value => $label)
                                            <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="text-sm" data-field="type" data-value="{{ $type }}">{{ $typeOptions[$type] ?? $type }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($editable)
                                    <input
                                        type="text"
                                        class="kt-input"
                                        data-field="label"
                                        name="elements[{{ $index }}][label]"
                                        value="{{ $row['label'] ?? '' }}"
                                        list="{{ $labelDatalistId }}"
                                        placeholder="Approved?"
                                        autocomplete="off"
                                    >
                                @else
                                    <span class="text-sm" data-field="label" data-value="{{ $row['label'] ?? '' }}">{{ $row['label'] ?? '' }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($editable)
                                    <select
                                        class="kt-select"
                                        data-field="stakeholder_need_id"
                                        name="elements[{{ $index }}][stakeholder_need_id]"
                                        @disabled(! $linkable)
                                    >
                                        <option value="">—</option>
                                        @foreach ($stakeholderNeedOptions as $opt)
                                            <option value="{{ $opt['value'] }}" @selected($needId === (string) ($opt['value'] ?? ''))>
                                                {{ $opt['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="text-sm" data-field="stakeholder_need_id" data-value="{{ $needId }}">
                                        {{ $linkable ? ($needLabel !== '' ? $needLabel : '—') : '—' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($editable)
                                    <select
                                        class="kt-select"
                                        data-field="lane_color"
                                        name="elements[{{ $index }}][lane_color]"
                                        @if ($laneFill !== '')
                                            data-swatch-fill="{{ $laneColor }}"
                                            style="--bassist-swatch-fill: {{ $laneFill }};"
                                        @endif
                                    >
                                        @foreach ($laneColorOptions as $value => $label)
                                            @php
                                                $optionFill = ($value !== '' && isset($laneColorPalette[$value]))
                                                    ? $laneColorPalette[$value]['fill']
                                                    : '';
                                            @endphp
                                            <option
                                                value="{{ $value }}"
                                                @selected($laneColor === $value)
                                                @if ($optionFill !== '')
                                                    style="background-color: {{ $optionFill }}; color: #0f172a;"
                                                @endif
                                            >{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="text-sm inline-flex items-center" data-field="lane_color" data-value="{{ $laneColor }}">
                                        @if ($laneColor !== '' && isset($laneColorPalette[$laneColor]))
                                            <span
                                                class="bassist-lane-color-swatch"
                                                style="background: {{ $laneColorPalette[$laneColor]['fill'] }}; border-color: {{ $laneColorPalette[$laneColor]['stroke'] }};"
                                                aria-hidden="true"
                                            ></span>
                                        @endif
                                        {{ $laneColorLabel }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($editable)
                                    <select
                                        class="kt-select"
                                        data-field="element_color"
                                        name="elements[{{ $index }}][element_color]"
                                        @if ($elementFill !== '')
                                            data-swatch-fill="{{ $elementColor }}"
                                            style="--bassist-swatch-fill: {{ $elementFill }};"
                                        @endif
                                    >
                                        @foreach ($elementColorOptions as $value => $label)
                                            @php
                                                $optionFill = ($value !== '' && isset($elementColorPalette[$value]))
                                                    ? $elementColorPalette[$value]['fill']
                                                    : '';
                                            @endphp
                                            <option
                                                value="{{ $value }}"
                                                @selected($elementColor === $value)
                                                @if ($optionFill !== '')
                                                    style="background-color: {{ $optionFill }}; color: #0f172a;"
                                                @endif
                                            >{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="text-sm inline-flex items-center" data-field="element_color" data-value="{{ $elementColor }}">
                                        @if ($elementColor !== '' && isset($elementColorPalette[$elementColor]))
                                            <span
                                                class="bassist-lane-color-swatch"
                                                style="background: {{ $elementColorPalette[$elementColor]['fill'] }}; border-color: {{ $elementColorPalette[$elementColor]['stroke'] }};"
                                                aria-hidden="true"
                                            ></span>
                                        @endif
                                        {{ $elementColorLabel }}
                                    </span>
                                @endif
                            </td>
                            @if ($editable)
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon"
                                            data-move-element="up"
                                            title="{{ __('ui.element_move_up') }}"
                                            aria-label="{{ __('ui.element_move_up') }}"
                                        >
                                            ↑
                                        </button>
                                        <button
                                            type="button"
                                            class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon"
                                            data-move-element="down"
                                            title="{{ __('ui.element_move_down') }}"
                                            aria-label="{{ __('ui.element_move_down') }}"
                                        >
                                            ↓
                                        </button>
                                        <button
                                            type="button"
                                            class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon"
                                            data-add-element
                                            title="{{ __('ui.add_element_row') }}"
                                            aria-label="{{ __('ui.add_element_row') }}"
                                        >
                                            <i class="ki-filled ki-plus"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon"
                                            data-remove-element
                                            title="{{ __('ui.remove_element_row') }}"
                                            aria-label="{{ __('ui.remove_element_row') }}"
                                        >
                                            <i class="ki-filled ki-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($editable)
            {{-- Same pattern as C4 relationship from_key/to_key: native datalist + kt-input free text. --}}
            <datalist id="{{ $laneDatalistId }}" data-lane-names-list></datalist>
            {{-- From → Label edges: suggest existing node labels for From and Label (To). --}}
            <datalist id="{{ $labelDatalistId }}" data-label-names-list></datalist>
        @endif
    </div>

    @if ($editable)
        <template data-element-row-template>
            <tr data-element-row>
                <td>
                    <input type="hidden" data-field="id" name="elements[__INDEX__][id]" value="">
                    <input type="hidden" data-field="code" name="elements[__INDEX__][code]" value="">
                    <input type="text" class="kt-input" data-field="lane" name="elements[__INDEX__][lane]" value="" list="{{ $laneDatalistId }}" placeholder="Support" autocomplete="off">
                </td>
                <td>
                    <input type="text" class="kt-input" data-field="from" name="elements[__INDEX__][from]" value="" list="{{ $labelDatalistId }}" placeholder="Review" autocomplete="off">
                </td>
                <td>
                    <input type="text" class="kt-input" data-field="line_title" name="elements[__INDEX__][line_title]" value="" placeholder="Yes" autocomplete="off">
                </td>
                <td>
                    <select class="kt-select" data-field="type" name="elements[__INDEX__][type]">
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($value === 'process')>{{ $label }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" class="kt-input" data-field="label" name="elements[__INDEX__][label]" value="" list="{{ $labelDatalistId }}" placeholder="Approved?" autocomplete="off">
                </td>
                <td>
                    <select class="kt-select" data-field="stakeholder_need_id" name="elements[__INDEX__][stakeholder_need_id]">
                        <option value="">—</option>
                        @foreach ($stakeholderNeedOptions as $opt)
                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select class="kt-select" data-field="lane_color" name="elements[__INDEX__][lane_color]">
                        @foreach ($laneColorOptions as $value => $label)
                            @php
                                $optionFill = ($value !== '' && isset($laneColorPalette[$value]))
                                    ? $laneColorPalette[$value]['fill']
                                    : '';
                            @endphp
                            <option
                                value="{{ $value }}"
                                @if ($optionFill !== '')
                                    style="background-color: {{ $optionFill }}; color: #0f172a;"
                                @endif
                            >{{ $label }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select class="kt-select" data-field="element_color" name="elements[__INDEX__][element_color]">
                        @foreach ($elementColorOptions as $value => $label)
                            @php
                                $optionFill = ($value !== '' && isset($elementColorPalette[$value]))
                                    ? $elementColorPalette[$value]['fill']
                                    : '';
                            @endphp
                            <option
                                value="{{ $value }}"
                                @if ($optionFill !== '')
                                    style="background-color: {{ $optionFill }}; color: #0f172a;"
                                @endif
                            >{{ $label }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <div class="flex items-center justify-end gap-1">
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon" data-move-element="up" title="{{ __('ui.element_move_up') }}" aria-label="{{ __('ui.element_move_up') }}">↑</button>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon" data-move-element="down" title="{{ __('ui.element_move_down') }}" aria-label="{{ __('ui.element_move_down') }}">↓</button>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon" data-add-element title="{{ __('ui.add_element_row') }}" aria-label="{{ __('ui.add_element_row') }}">
                            <i class="ki-filled ki-plus"></i>
                        </button>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon" data-remove-element title="{{ __('ui.remove_element_row') }}" aria-label="{{ __('ui.remove_element_row') }}">
                            <i class="ki-filled ki-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        </template>
    @endif

    @if ($showDiagram)
        <div>
            <div class="flex items-center justify-between gap-2 mb-3">
                <h4 class="text-sm font-semibold text-foreground">{{ __('ui.diagram_preview') }}</h4>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($editable)
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-primary" data-preview-diagram>
                            {{ __('ui.preview_diagram') }}
                        </button>
                    @endif
                    <button
                        type="button"
                        class="kt-btn kt-btn-sm kt-btn-outline"
                        data-preview-diagram-modal
                        title="{{ __('ui.preview_diagram_modal_shortcut') }}"
                        aria-keyshortcuts="Alt+Q"
                    >
                        {{ __('ui.preview_diagram_modal') }}
                    </button>
                </div>
            </div>
            <div class="border border-border rounded-lg p-4 bg-white overflow-x-auto min-h-24">
                <pre class="mermaid bassist-mermaid" data-mermaid-preview>@if ($editable){{ __('ui.preview_swimlane_hint') }}@endif</pre>
            </div>
            @include('pages.partials.mermaid-source', [
                'editorId' => 'swimlane_mermaid_source_'.uniqid(),
                'readonly' => ! $editable,
                'showApply' => $editable,
            ])
        </div>
    @endif
</div>
