{{-- Need-spine graph (Mermaid). Nodes open the record in the shared modal; dashed amber = gap. --}}
@if (! $graph || $graph['nodes'] === 0)
    <x-empty-state icon="share" :title="__('ui.trace_graph_empty_title')" :hint="__('ui.trace_graph_empty_hint')" compact />
@elseif ($graph['too_large'])
    <x-empty-state icon="filter" :title="__('ui.trace_graph_too_large_title', ['count' => $graph['nodes']])" :hint="__('ui.trace_graph_too_large_hint')" compact />
@else
    <div class="ba-trace-graph" data-trace-graph>
        <div class="ba-trace-graph__legend" aria-hidden="true">
            <span><i class="ba-legend ba-legend--need"></i>{{ __('ui.business_need') }}</span>
            <span><i class="ba-legend ba-legend--objective"></i>{{ __('ui.business_objective') }}</span>
            <span><i class="ba-legend ba-legend--story"></i>{{ __('ui.stakeholder_need') }}</span>
            <span><i class="ba-legend ba-legend--solution"></i>{{ __('ui.solution_requirement') }}</span>
            <span><i class="ba-legend ba-legend--proof"></i>{{ __('ui.trace_coverage_proof') }}</span>
            <span><i class="ba-legend ba-legend--gap"></i>{{ __('ui.trace_graph_gap') }}</span>
        </div>
        <div class="ba-trace-graph__canvas" data-trace-graph-canvas>
            <p class="text-sm text-muted-foreground">{{ __('ui.trace_graph_loading') }}</p>
        </div>
        <script type="application/json" data-trace-graph-source>@json(['mermaid' => $graph['mermaid'], 'links' => $graph['links']])</script>
        <p class="ba-trace-graph__hint">{{ __('ui.trace_graph_hint') }}</p>
    </div>
    @vite(['resources/js/traceability-graph.js'])
@endif
