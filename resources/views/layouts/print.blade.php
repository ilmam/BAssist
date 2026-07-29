<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', config('app.name'))</title>
    <style>
        :root {
            --ink: #1a1a1a;
            --muted: #5c5c5c;
            --line: #d4d4d4;
            --surface: #f7f7f7;
            --accent: #0f3d2e;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: var(--ink);
            background: #fff;
            font: 14px/1.5 Georgia, "Times New Roman", Times, serif;
        }

        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.75rem 1.25rem;
            background: #fff;
            border-bottom: 1px solid var(--line);
        }

        .print-toolbar__hint {
            color: var(--muted);
            font: 13px/1.4 system-ui, sans-serif;
        }

        .print-toolbar__actions {
            display: flex;
            gap: 0.5rem;
        }

        .print-btn {
            appearance: none;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--ink);
            border-radius: 6px;
            padding: 0.45rem 0.9rem;
            font: 600 13px/1.2 system-ui, sans-serif;
            cursor: pointer;
            text-decoration: none;
        }

        .print-btn--primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .print-btn:hover { opacity: 0.92; }

        .print-pack {
            max-width: 920px;
            margin: 0 auto;
            padding: 1.5rem 1.25rem 3rem;
        }

        .cover {
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--ink);
            margin-bottom: 2rem;
        }

        .cover__eyebrow {
            margin: 0 0 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font: 600 11px/1.2 system-ui, sans-serif;
            color: var(--muted);
        }

        .cover h1 {
            margin: 0 0 0.5rem;
            font-size: 2rem;
            line-height: 1.2;
            font-weight: 700;
        }

        .cover__meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.5rem 1.25rem;
            margin-top: 1rem;
            font: 13px/1.4 system-ui, sans-serif;
            color: var(--muted);
        }

        .cover__meta strong {
            display: block;
            color: var(--ink);
            font-weight: 600;
        }

        .cover__description {
            margin: 1rem 0 0;
            max-width: 42rem;
        }

        h2.section-title {
            margin: 2.25rem 0 0.85rem;
            padding-bottom: 0.35rem;
            border-bottom: 1px solid var(--line);
            font-size: 1.25rem;
            break-after: avoid;
            page-break-after: avoid;
        }

        h3.item-title {
            margin: 1.25rem 0 0;
            font-size: 1.05rem;
            break-after: avoid;
            page-break-after: avoid;
        }

        h3.item-title .artifact__code {
            margin-right: 0.45rem;
        }

        .muted { color: var(--muted); }
        .empty {
            padding: 0.75rem 1rem;
            background: var(--surface);
            border: 1px dashed var(--line);
            border-radius: 6px;
            color: var(--muted);
            font: 13px/1.4 system-ui, sans-serif;
        }

        .artifact {
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .artifact:last-child { border-bottom: 0; }

        .artifact__code {
            font: 600 12px/1.2 ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            color: var(--muted);
        }

        .artifact__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem 1.25rem;
            margin: 0;
            font: 13px/1.4 system-ui, sans-serif;
        }

        .artifact__meta span {
            white-space: nowrap;
        }

        .artifact__meta strong {
            color: var(--muted);
            font-weight: 600;
            margin-right: 0.35rem;
        }

        .artifact__panel {
            margin-top: 0.75rem;
            margin-bottom: 1.25rem;
            padding: 0.75rem 0.9rem;
            border: 1px solid #e8e8e8;
            border-radius: 8px;
            background: #fcfcfc;
        }

        .artifact__panel .artifact__meta {
            margin-bottom: 0;
        }

        .artifact__panel .kv {
            margin: 0.65rem 0 0;
            padding-top: 0.65rem;
            border-top: 1px solid #ececec;
        }

        .artifact__panel .kv:first-child {
            margin-top: 0;
            padding-top: 0;
            border-top: 0;
        }

        .kv {
            display: grid;
            grid-template-columns: 9rem 1fr;
            gap: 0.25rem 0.75rem;
            margin: 0.5rem 0;
            font: 13px/1.45 system-ui, sans-serif;
        }

        .kv dt { color: var(--muted); }
        .kv dd { margin: 0; }

        .prose { white-space: pre-wrap; margin: 0.35rem 0 0; }

        .diagram {
            margin-top: 0.75rem;
            padding: 0.75rem;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            overflow-x: auto;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .diagram .mermaid,
        .diagram .bassist-mermaid {
            margin: 0;
            background: transparent;
        }

        .diagram .mermaid svg,
        .diagram .bassist-mermaid svg {
            max-width: 100%;
            height: auto;
        }

        table.matrix {
            width: 100%;
            border-collapse: collapse;
            font: 12px/1.35 system-ui, sans-serif;
        }

        table.matrix th,
        table.matrix td {
            border: 1px solid var(--line);
            padding: 0.4rem 0.5rem;
            text-align: left;
            vertical-align: top;
        }

        table.matrix th {
            background: var(--surface);
            font-weight: 600;
        }

        table.matrix tr.has-gap td {
            background: #fff8f0;
        }

        .summary {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1.25rem;
            margin: 0.5rem 0 0.85rem;
            font: 13px/1.3 system-ui, sans-serif;
            color: var(--muted);
        }

        .summary strong { color: var(--ink); }

        @media print {
            .no-print { display: none !important; }

            body {
                font-size: 11pt;
            }

            .print-pack {
                max-width: none;
                margin: 0;
                padding: 0;
            }

            .cover {
                break-inside: avoid;
                page-break-inside: avoid;
                break-after: avoid;
                page-break-after: avoid;
            }

            h2.section-title {
                break-before: auto;
                page-break-before: auto;
                break-after: avoid;
                page-break-after: avoid;
                margin-top: 1.5rem;
            }

            /* Keep section titles with the first following block */
            h2.section-title + * {
                break-before: avoid;
                page-break-before: avoid;
            }

            h3.item-title {
                break-after: avoid;
                page-break-after: avoid;
            }

            .summary {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .artifact {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .artifact__panel,
            .kv {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            table.matrix thead {
                break-after: avoid;
                page-break-after: avoid;
            }

            table.matrix tbody tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            a { color: inherit; text-decoration: none; }
        }

        @page {
            margin: 14mm;
        }
    </style>
    @stack('styles')
</head>
<body>
    @yield('toolbar')

    <main class="print-pack">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
