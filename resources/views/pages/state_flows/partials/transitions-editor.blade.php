@php
    use App\Services\StateDiagramMermaidGenerator;

    $generator = app(StateDiagramMermaidGenerator::class);
    $rawTransitions = is_array($transitions ?? null) ? $transitions : [];
    $bodyOnly = (bool) ($bodyOnly ?? false);

    // Prefer showing terminals in the table as `*`. Legacy initial/final (if passed)
    // are folded into rows so the separate fields are no longer needed.
    if ($bodyOnly) {
        $transitionRows = $generator->toEditorRows(
            $generator->composeFromForm(
                $rawTransitions,
                $initialState ?? null,
                $finalStates ?? null
            )
        );
    } else {
        $transitionRows = $generator->toEditorRows($rawTransitions);
    }

    if ($transitionRows === []) {
        $transitionRows = [['from' => '', 'to' => '', 'trigger' => '']];
    }

    $editable = $editable ?? true;
    $autoRender = $autoRender ?? ! $editable;
    $showTitleField = $showTitleField ?? false;
    $flowTitle = $flowTitle ?? '';
    $showDiagram = $showDiagram ?? true;
@endphp

@once
    <style>
        .bassist-mermaid svg .state-start circle,
        .bassist-mermaid svg g.state-start circle,
        .bassist-mermaid svg .node.state-start circle {
            fill: #111827 !important;
            stroke: #111827 !important;
        }

        .bassist-mermaid svg .state-end > circle:nth-child(1),
        .bassist-mermaid svg g.state-end > circle:nth-child(1),
        .bassist-mermaid svg .node.state-end > circle:nth-child(1) {
            fill: #ffffff !important;
            stroke: #111827 !important;
            stroke-width: 1.5px !important;
        }

        .bassist-mermaid svg .state-end > circle:nth-child(2),
        .bassist-mermaid svg g.state-end > circle:nth-child(2),
        .bassist-mermaid svg .node.state-end > circle:nth-child(2) {
            fill: #111827 !important;
            stroke: #111827 !important;
        }
    </style>
@endonce

<div
    data-state-flow-editor
    @if ($autoRender) data-auto-render="1" @endif
    @if ($flowTitle !== '') data-flow-title-value="{{ $flowTitle }}" @endif
    class="space-y-5"
>
    @if ($showTitleField)
        <input type="hidden" data-flow-title value="{{ $flowTitle }}">
    @endif

    <div>
        <h4 class="text-sm font-semibold text-foreground mb-1">{{ __('ui.transitions') }}</h4>
        <p class="text-xs text-muted-foreground mb-3">{{ __('ui.transitions_terminal_hint') }}</p>

        <div class="overflow-x-auto border border-border rounded-lg">
            <table class="kt-table table-auto w-full" data-transitions-table>
                <thead>
                    <tr>
                        <th class="min-w-40">{{ __('ui.transition_from') }}</th>
                        <th class="min-w-40">{{ __('ui.transition_to') }}</th>
                        <th class="min-w-48">{{ __('ui.transition_trigger') }}</th>
                        @if ($editable)
                            <th class="w-28">{{ __('ui.actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transitionRows as $index => $row)
                        <tr data-transition-row>
                            <td>
                                @if ($editable)
                                    <input
                                        type="text"
                                        class="kt-input"
                                        data-field="from"
                                        name="transitions[{{ $index }}][from]"
                                        value="{{ $row['from'] ?? '' }}"
                                        placeholder="{{ __('ui.transition_from_placeholder') }}"
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
                                        data-field="to"
                                        name="transitions[{{ $index }}][to]"
                                        value="{{ $row['to'] ?? '' }}"
                                        placeholder="{{ __('ui.transition_to_placeholder') }}"
                                        autocomplete="off"
                                    >
                                @else
                                    <span class="text-sm" data-field="to" data-value="{{ $row['to'] ?? '' }}">{{ $row['to'] ?? '' }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($editable)
                                    <input
                                        type="text"
                                        class="kt-input"
                                        data-field="trigger"
                                        name="transitions[{{ $index }}][trigger]"
                                        value="{{ $row['trigger'] ?? '' }}"
                                        placeholder="{{ __('ui.transition_trigger_placeholder') }}"
                                        autocomplete="off"
                                    >
                                @else
                                    <span class="text-sm" data-field="trigger" data-value="{{ $row['trigger'] ?? '' }}">{{ $row['trigger'] ?? '' }}</span>
                                @endif
                            </td>
                            @if ($editable)
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            class="kt-btn kt-btn-sm kt-btn-secondary"
                                            data-add-transition
                                            title="{{ __('ui.add_transition_row') }}"
                                            aria-label="{{ __('ui.add_transition_row') }}"
                                        >
                                            {{ __('ui.add_transition_row') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon"
                                            data-remove-transition
                                            title="{{ __('ui.remove_transition_row') }}"
                                            aria-label="{{ __('ui.remove_transition_row') }}"
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
        <template data-transition-row-template>
            <tr data-transition-row>
                <td>
                    <input type="text" class="kt-input" data-field="from" name="transitions[__INDEX__][from]" value="" placeholder="{{ __('ui.transition_from_placeholder') }}" autocomplete="off">
                </td>
                <td>
                    <input type="text" class="kt-input" data-field="to" name="transitions[__INDEX__][to]" value="" placeholder="{{ __('ui.transition_to_placeholder') }}" autocomplete="off">
                </td>
                <td>
                    <input type="text" class="kt-input" data-field="trigger" name="transitions[__INDEX__][trigger]" value="" placeholder="{{ __('ui.transition_trigger_placeholder') }}" autocomplete="off">
                </td>
                <td>
                    <div class="flex items-center justify-end gap-1">
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-add-transition title="{{ __('ui.add_transition_row') }}" aria-label="{{ __('ui.add_transition_row') }}">
                            {{ __('ui.add_transition_row') }}
                        </button>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon" data-remove-transition title="{{ __('ui.remove_transition_row') }}" aria-label="{{ __('ui.remove_transition_row') }}">
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
                <pre class="mermaid bassist-mermaid" data-mermaid-preview>@if ($editable){{ __('ui.preview_diagram_hint') }}@endif</pre>
            </div>
            @include('pages.partials.mermaid-source', [
                'editorId' => 'state_mermaid_source_'.uniqid(),
            ])
        </div>
    @endif
</div>
