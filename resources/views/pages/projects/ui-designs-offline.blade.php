<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $project->name }} — UI designs</title>
    <style>
        body { margin: 0; background: #f4f5f7; color: #1a1a1a; font: 15px/1.5 system-ui, sans-serif; }
        main { max-width: 900px; margin: 0 auto; padding: 2rem 1.25rem 4rem; }
        h1 { margin: 0 0 .25rem; font-size: 1.6rem; }
        .meta { color: #5c5c5c; margin: 0 0 1.5rem; font-size: .9rem; }
        article { background: #fff; border: 1px solid #d4d4d4; border-radius: 10px; padding: 1.25rem 1.5rem 1.5rem; margin: 0 0 1.5rem; }
        article h2 { margin: 0 0 .25rem; font-size: 1.2rem; }
        .realizes { color: #5c5c5c; font-size: .85rem; margin: 0 0 .75rem; }
        .draw { margin: 1rem 0; }
        .draw .salt-frame { max-width: 760px; margin: 0 auto; }
        details { margin-top: .5rem; }
        pre { background: #f7f7f7; border: 1px solid #e0e0e0; border-radius: 6px; padding: .75rem 1rem; overflow: auto; font: 12px/1.55 ui-monospace, Menlo, monospace; }
        {!! $css !!}
    </style>
</head>
<body>
<main>
    <h1>{{ $project->name }}: UI designs</h1>
    <p class="meta">Generated {{ $generated_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }} from BAssist. {{ count($screens) }} screen(s).</p>

    @forelse ($screens as $item)
        <article>
            <h2>{{ $item['number'] }}. {{ $item['model']->title }}</h2>
            <p class="realizes">
                Status: {{ $item['model']->status?->name ?: '—' }}
                · Realizes: {{ $item['requirements'] === [] ? '—' : implode('; ', $item['requirements']) }}
            </p>
            @if (filled($item['model']->description))
                <p>{{ $item['model']->description }}</p>
            @endif
            <div class="draw" data-draw></div>
            <details>
                <summary>PlantUML (Salt) text — <code>{{ $item['file'] }}</code></summary>
                <pre data-source>{{ $item['salt'] }}</pre>
            </details>
        </article>
    @empty
        <p>No UI designs yet.</p>
    @endforelse
</main>
<script>{!! $js !!}</script>
<script>
    document.querySelectorAll('article').forEach(function (article) {
        var draw = article.querySelector('[data-draw]');
        var source = article.querySelector('[data-source]');
        if (draw && source) window.SaltWireframe.render(source.textContent, draw);
    });
</script>
</body>
</html>
