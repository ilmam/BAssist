@php
    use App\Services\StateDiagramMermaidGenerator;

    $generator = app(StateDiagramMermaidGenerator::class);
    $rawTransitions = is_array($transitions ?? null) ? $transitions : [];
    $bodyOnly = (bool) ($bodyOnly ?? false);

    if ($bodyOnly) {
        $initialState = $initialState ?? null;
        $finalStates = $finalStates ?? '';
        $transitionRows = $rawTransitions;
    } else {
        $split = $generator->splitTerminals($rawTransitions);
        $initialState = $initialState ?? $split['initial'];
        $finalStates = $finalStates ?? implode(', ', $split['finals']);
        $transitionRows = $split['transitions'];
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
    data-initial-state="{{ $initialState ?? '' }}"
    data-final-states="{{ $finalStates ?? '' }}"
    class="space-y-5"
>
    @if ($showTitleField)
        <input type="hidden" data-flow-title value="{{ $flowTitle }}">
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="kt-form-label mb-1.5" for="initial_state">{{ __('ui.initial_state') }}</label>
            @if ($editable)
                <input
                    id="initial_state"
                    type="text"
                    class="kt-input"
                    name="initial_state"
                    value="{{ $initialState ?? '' }}"
                    placeholder="{{ __('ui.initial_state_placeholder') }}"
                    autocomplete="off"
                >
            @else
                <div class="text-sm">{{ filled($initialState) ? $initialState : '—' }}</div>
            @endif
            <div class="text-xs text-muted-foreground mt-1">{{ __('ui.initial_state_hint') }}</div>
        </div>
        <div>
            <label class="kt-form-label mb-1.5" for="final_states">{{ __('ui.final_states') }}</label>
            @if ($editable)
                <input
                    id="final_states"
                    type="text"
                    class="kt-input"
                    name="final_states"
                    value="{{ $finalStates ?? '' }}"
                    placeholder="{{ __('ui.final_states_placeholder') }}"
                    autocomplete="off"
                >
            @else
                <div class="text-sm">{{ filled($finalStates) ? $finalStates : '—' }}</div>
            @endif
            <div class="text-xs text-muted-foreground mt-1">{{ __('ui.final_states_hint') }}</div>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-semibold text-foreground mb-3">{{ __('ui.transitions') }}</h4>

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
                                        placeholder="Still"
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
                                        placeholder="Moving"
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
                                        placeholder="Approve"
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
                    <input type="text" class="kt-input" data-field="from" name="transitions[__INDEX__][from]" value="" placeholder="Still" autocomplete="off">
                </td>
                <td>
                    <input type="text" class="kt-input" data-field="to" name="transitions[__INDEX__][to]" value="" placeholder="Moving" autocomplete="off">
                </td>
                <td>
                    <input type="text" class="kt-input" data-field="trigger" name="transitions[__INDEX__][trigger]" value="" placeholder="Approve" autocomplete="off">
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
