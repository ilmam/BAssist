@php
    $architecture = $architecture ?? null;
    $hasArchitecture = is_array($architecture) && ($architecture['views'] ?? []) !== [];
@endphp

@if ($swimlane_flows === [] && $state_flows === [] && ! $hasArchitecture)
    <p class="empty">{{ __('ui.export_none', ['items' => __('ui.diagrams')]) }}</p>
@endif

@include('pages.projects.babok.partials.architecture-c4', [
    'architecture' => $architecture,
])

@if ($swimlane_flows !== [])
    <h2 class="section-title{{ $hasArchitecture ? ' section-title--break' : '' }}">{{ __('ui.swimlane_flows') }}</h2>
    @foreach ($swimlane_flows as $item)
        @php
            $flow = $item['model'];
            $mermaidBody = trim($item['mermaid'] ?? '');
        @endphp
        <article class="artifact">
            <h3 class="item-title">{{ $flow->title }}</h3>
            @if ($flow->description)
                <p class="prose">{{ $flow->description }}</p>
            @endif
            @if ($mermaidBody !== '')
                <div
                    class="diagram"
                    data-export-diagram
                    data-mermaid="{{ base64_encode($mermaidBody) }}"
                ></div>
            @else
                <p class="empty">{{ __('ui.no_elements') }}</p>
            @endif
        </article>
    @endforeach
@endif

@if ($state_flows !== [])
    <h2 class="section-title section-title--break">{{ __('ui.state_flows') }}</h2>
    @foreach ($state_flows as $item)
        @php
            $flow = $item['model'];
            $mermaidBody = trim($item['mermaid'] ?? '');
        @endphp
        <article class="artifact">
            <h3 class="item-title">{{ $flow->title }}</h3>
            @if ($flow->description)
                <p class="prose">{{ $flow->description }}</p>
            @endif
            @if ($mermaidBody !== '')
                <div
                    class="diagram"
                    data-export-diagram
                    data-mermaid="{{ base64_encode($mermaidBody) }}"
                ></div>
            @else
                <p class="empty">{{ __('ui.no_transitions') }}</p>
            @endif
        </article>
    @endforeach
@endif
