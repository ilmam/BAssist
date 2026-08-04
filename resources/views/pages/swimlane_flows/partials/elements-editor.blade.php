@php
    use App\Support\ProcessStepSatisfyType;

    $elementRows = is_array($elements ?? null) ? $elements : [];
    if ($elementRows === []) {
        $elementRows = [['lane' => '', 'from' => '', 'type' => 'process', 'label' => '', 'line_title' => '', 'code' => '', 'satisfy_type' => null, 'satisfy_id' => null]];
    }

    $editable = $editable ?? true;
    $autoRender = $autoRender ?? ! $editable;
    $showTitleField = $showTitleField ?? false;
    $flowTitle = $flowTitle ?? '';
    $showDiagram = $showDiagram ?? true;
    $direction = strtoupper((string) ($direction ?? 'TB')) === 'LR' ? 'LR' : 'TB';
    $satisfyOptions = is_array($satisfyOptions ?? null) ? $satisfyOptions : [];
    $satisfyOptionsUrl = $satisfyOptionsUrl ?? route('swimlane_flows.satisfy-options');
    $satisfyLabels = collect($satisfyOptions)->mapWithKeys(
        fn (array $opt) => [($opt['value'] ?? '') => ($opt['label'] ?? '')]
    )->all();

    $typeOptions = [
        'start' => __('ui.element_type_start'),
        'process' => __('ui.element_type_process'),
        'decision' => __('ui.element_type_decision'),
        'end' => __('ui.element_type_end'),
    ];
@endphp

@once
    <style>
        .bassist-mermaid {
            background: #ffffff;
        }
    </style>
@endonce

<div
    data-swimlane-flow-editor
    @if ($autoRender) data-auto-render="1" @endif
    @if ($flowTitle !== '') data-flow-title-value="{{ $flowTitle }}" @endif
    data-direction="{{ $direction }}"
    data-satisfy-options-url="{{ $satisfyOptionsUrl }}"
    class="space-y-5"
>
    @if ($showTitleField)
        <input type="hidden" data-flow-title value="{{ $flowTitle }}">
    @endif

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

    <div>
        <h4 class="text-sm font-semibold text-foreground mb-3">{{ __('ui.elements') }}</h4>

        <div class="overflow-x-auto border border-border rounded-lg">
            <table class="kt-table table-auto w-full" data-elements-table>
                <thead>
                    <tr>
                        <th class="min-w-24">{{ __('ui.element_code') }}</th>
                        <th class="min-w-36">{{ __('ui.element_lane') }}</th>
                        <th class="min-w-36">{{ __('ui.element_from') }}</th>
                        <th class="min-w-32">{{ __('ui.element_type') }}</th>
                        <th class="min-w-40">{{ __('ui.element_label') }}</th>
                        <th class="min-w-36">{{ __('ui.element_line_title') }}</th>
                        <th class="min-w-56">{{ __('ui.element_satisfy') }}</th>
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
                            $satisfyValue = ProcessStepSatisfyType::encode(
                                isset($row['satisfy_type']) ? (string) $row['satisfy_type'] : null,
                                isset($row['satisfy_id']) ? (int) $row['satisfy_id'] : null
                            );
                            if ($satisfyValue === '' && ! empty($row['satisfy'])) {
                                $satisfyValue = (string) $row['satisfy'];
                            }
                            $satisfyLabel = $satisfyLabels[$satisfyValue] ?? $satisfyValue;
                            $satisfiable = in_array($type, ['process', 'decision'], true);
                        @endphp
                        <tr data-element-row>
                            <td>
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
                            <td>
                                @if ($editable)
                                    <input
                                        type="text"
                                        class="kt-input"
                                        data-field="lane"
                                        name="elements[{{ $index }}][lane]"
                                        value="{{ $row['lane'] ?? '' }}"
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
                                        placeholder="Review"
                                        autocomplete="off"
                                    >
                                @else
                                    <span class="text-sm" data-field="from" data-value="{{ $row['from'] ?? '' }}">{{ $row['from'] ?? '' }}</span>
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
                                        placeholder="Approved?"
                                        autocomplete="off"
                                    >
                                @else
                                    <span class="text-sm" data-field="label" data-value="{{ $row['label'] ?? '' }}">{{ $row['label'] ?? '' }}</span>
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
                                    <select
                                        class="kt-select"
                                        data-field="satisfy"
                                        name="elements[{{ $index }}][satisfy]"
                                        @disabled(! $satisfiable)
                                    >
                                        <option value="">—</option>
                                        @foreach ($satisfyOptions as $opt)
                                            <option value="{{ $opt['value'] }}" @selected($satisfyValue === ($opt['value'] ?? ''))>
                                                {{ $opt['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="text-sm" data-field="satisfy" data-value="{{ $satisfyValue }}">
                                        {{ $satisfiable ? ($satisfyLabel !== '' ? $satisfyLabel : '—') : '—' }}
                                    </span>
                                @endif
                            </td>
                            @if ($editable)
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            class="kt-btn kt-btn-sm kt-btn-light"
                                            data-add-element
                                            title="{{ __('ui.add_element_row') }}"
                                            aria-label="{{ __('ui.add_element_row') }}"
                                        >
                                            {{ __('ui.add_element_row') }}
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
    </div>

    @if ($editable)
        <template data-element-row-template>
            <tr data-element-row>
                <td>
                    <input type="text" class="kt-input bg-muted/40" data-field="code" name="elements[__INDEX__][code]" value="" readonly tabindex="-1" placeholder="{{ __('ui.element_code_placeholder') }}" autocomplete="off">
                </td>
                <td>
                    <input type="text" class="kt-input" data-field="lane" name="elements[__INDEX__][lane]" value="" placeholder="Support" autocomplete="off">
                </td>
                <td>
                    <input type="text" class="kt-input" data-field="from" name="elements[__INDEX__][from]" value="" placeholder="Review" autocomplete="off">
                </td>
                <td>
                    <select class="kt-select" data-field="type" name="elements[__INDEX__][type]">
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($value === 'process')>{{ $label }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" class="kt-input" data-field="label" name="elements[__INDEX__][label]" value="" placeholder="Approved?" autocomplete="off">
                </td>
                <td>
                    <input type="text" class="kt-input" data-field="line_title" name="elements[__INDEX__][line_title]" value="" placeholder="Yes" autocomplete="off">
                </td>
                <td>
                    <select class="kt-select" data-field="satisfy" name="elements[__INDEX__][satisfy]">
                        <option value="">—</option>
                        @foreach ($satisfyOptions as $opt)
                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <div class="flex items-center justify-end gap-1">
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-light" data-add-element title="{{ __('ui.add_element_row') }}" aria-label="{{ __('ui.add_element_row') }}">
                            {{ __('ui.add_element_row') }}
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
                @if ($editable)
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary" data-preview-diagram>
                        {{ __('ui.preview_diagram') }}
                    </button>
                @endif
            </div>
            <div class="border border-border rounded-lg p-4 bg-white overflow-x-auto min-h-24">
                <pre class="mermaid bassist-mermaid" data-mermaid-preview>@if ($editable){{ __('ui.preview_swimlane_hint') }}@endif</pre>
            </div>
            <details class="mt-3">
                <summary class="text-xs text-muted-foreground cursor-pointer">Mermaid source</summary>
                <pre class="mt-2 text-xs p-3 border border-border rounded-lg overflow-x-auto whitespace-pre-wrap" data-mermaid-source></pre>
            </details>
        </div>
    @endif
</div>
