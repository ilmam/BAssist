@extends('layouts.print')

@section('title', ($project->name ?? __('ui.project')).' — '.__('ui.ui_designs_pack'))

@section('toolbar')
    <div class="print-toolbar no-print">
        <p class="print-toolbar__hint">{{ __('ui.project_export_print_hint') }}</p>
        <div class="print-toolbar__actions">
            <a class="print-btn" href="{{ route('projects.dashboard', $project) }}">{{ __('ui.back_to_project') }}</a>
            <a class="print-btn" href="{{ route('projects.ui-designs.download', $project) }}">{{ __('ui.ui_designs_download') }}</a>
            <button type="button" class="print-btn print-btn--primary" data-print-pack>
                {{ __('ui.print_to_pdf') }}
            </button>
        </div>
    </div>
@endsection

@section('content')
    <link rel="stylesheet" href="{{ asset('css/salt-wireframe.css').'?v='.@filemtime(public_path('css/salt-wireframe.css')) }}">
    <style>
        .ui-screen { margin-top: 2rem; }
        .ui-screen + .ui-screen { break-before: page; page-break-before: always; }
        .ui-screen__meta { color: var(--muted); font: 13px/1.5 system-ui, sans-serif; margin: .25rem 0 1rem; }
        .ui-screen__meta strong { color: var(--ink); }
        .ui-screen__draw { border: 1px solid var(--line); border-radius: 6px; padding: 1.25rem; background: #fff; margin: 1rem 0; break-inside: avoid; page-break-inside: avoid; }
        .ui-screen__draw .salt-frame { width: 100%; margin: 0; }
        .ui-screen__source { border: 1px solid var(--line); border-radius: 6px; background: var(--surface); padding: .75rem 1rem; font: 12px/1.55 ui-monospace, SFMono-Regular, Menlo, monospace; white-space: pre; overflow: auto; break-inside: avoid; page-break-inside: avoid; }
        .ui-screen__label { font: 600 12px/1 system-ui, sans-serif; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin: 1.25rem 0 .4rem; }
    </style>

    <header class="cover">
        <p class="cover__eyebrow">{{ __('ui.ui_designs_pack') }}</p>
        <h1>{{ $project->name }}</h1>
        @if ($project->description)
            <p class="cover__description">{{ $project->description }}</p>
        @endif
        <div class="cover__meta">
            <div><strong>{{ __('ui.code') }}</strong> {{ $project->code ?: '—' }}</div>
            <div><strong>{{ __('ui.workspace') }}</strong> {{ $project->workspace?->name ?: '—' }}</div>
            <div><strong>{{ __('ui.ui_designs') }}</strong> {{ count($screens) }}</div>
            <div><strong>{{ __('ui.generated_at') }}</strong> {{ $generated_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</div>
        </div>
    </header>

    @if ($screens === [])
        <p class="empty">{{ __('ui.ui_designs_none') }}</p>
    @else
        <nav class="export-toc" aria-label="{{ __('ui.table_of_contents') }}">
            <h2 class="section-title">{{ __('ui.table_of_contents') }}</h2>
            <ol class="export-toc__list">
                @foreach ($screens as $item)
                    <li><a href="#screen-{{ $item['model']->id }}">{{ $item['model']->title }}</a></li>
                @endforeach
            </ol>
        </nav>

        @foreach ($screens as $item)
            @php $screen = $item['model']; @endphp
            <section class="ui-screen" id="screen-{{ $screen->id }}">
                <h2 class="section-title">{{ $item['number'] }}. {{ $screen->title }}</h2>
                <p class="ui-screen__meta">
                    <strong>{{ __('ui.status') }}:</strong> {{ $screen->status?->name ?: '—' }}
                    &nbsp;·&nbsp;
                    <strong>{{ __('ui.screen_realizes') }}:</strong>
                    {{ $item['requirements'] === [] ? __('ui.screen_realizes_none') : implode('; ', $item['requirements']) }}
                </p>
                @if (filled($screen->description))
                    <p>{{ $screen->description }}</p>
                @endif

                <div class="ui-screen__draw" data-salt-draw></div>

                <p class="ui-screen__label">{{ __('ui.screen_salt_title') }}</p>
                <pre class="ui-screen__source" data-salt-source>{{ $item['salt'] }}</pre>
            </section>
        @endforeach

        {{-- Drawn by the same small renderer as the editor; each screen shows its own Salt text. --}}
        <script src="{{ asset('js/salt-wireframe.js').'?v='.@filemtime(public_path('js/salt-wireframe.js')) }}"></script>
        <script>
            document.querySelectorAll('.ui-screen').forEach(function (section) {
                var draw = section.querySelector('[data-salt-draw]');
                var source = section.querySelector('[data-salt-source]');
                try {
                    window.SaltWireframe.render(source.textContent, draw);
                    window.SaltWireframe.fit(draw);
                } catch (e) {
                    draw.textContent = 'Could not draw this screen: ' + e.message;
                }
            });
            // A wide design is scaled to the page: fit again when the width changes or before printing.
            function fitAll() { document.querySelectorAll('[data-salt-draw]').forEach(function (d) { window.SaltWireframe.fit(d); }); }
            window.addEventListener('resize', fitAll);
            window.addEventListener('beforeprint', fitAll);
            window.addEventListener('afterprint', fitAll);
        </script>
    @endif
@endsection
