@php
    $architecture = $architecture ?? null;
    $architectureViews = is_array($architecture) ? ($architecture['views'] ?? []) : [];
    $architectureModel = is_array($architecture) ? ($architecture['model'] ?? null) : null;
@endphp

@if ($architectureViews !== [])
    <h2 @if (! empty($sectionId)) id="{{ $sectionId }}" @endif class="section-title{{ ! empty($sectionBreak) ? ' section-title--break' : '' }}">
        {{ __('ui.architecture_c4') }}
    </h2>
    @if ($architectureModel)
        <article class="artifact">
            <h3 class="item-title">{{ $architectureModel->title }}</h3>
            <div class="artifact__panel">
                <div class="artifact__meta">
                    <span><strong>{{ __('ui.status') }}</strong>{{ $architectureModel->status?->name ?: '—' }}</span>
                </div>
            </div>
            @if ($architectureModel->description)
                <p class="prose">{{ $architectureModel->description }}</p>
            @endif
        </article>
    @endif
    @foreach ($architectureViews as $view)
        @php
            $mermaidBody = trim($view['mermaid'] ?? '');
        @endphp
        <article class="artifact">
            <h3 class="item-title">{{ $view['title'] ?? __('ui.architecture_c4') }}</h3>
            @if ($mermaidBody !== '')
                <div
                    class="diagram"
                    data-export-diagram
                    data-mermaid="{{ base64_encode($mermaidBody) }}"
                ></div>
            @endif
        </article>
    @endforeach
@endif
