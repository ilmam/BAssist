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

        /* Tall Mermaid figures must be allowed to split or print leaves blank pages. */
        .artifact.artifact--diagram {
            break-inside: auto;
            page-break-inside: auto;
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
            width: 100%;
            max-width: 100%;
            overflow: hidden;
            break-inside: auto;
            page-break-inside: auto;
        }

        .diagram .mermaid,
        .diagram .bassist-mermaid {
            margin: 0;
            width: 100%;
            max-width: 100%;
            background: transparent;
            overflow: hidden;
        }

        .diagram--compact {
            max-width: 22rem;
            margin-inline: auto;
        }

        .diagram .mermaid svg,
        .diagram .bassist-mermaid svg {
            max-width: 100% !important;
            width: auto;
            height: auto !important;
            display: block;
            margin-inline: auto;
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

            .artifact.artifact--diagram,
            .artifact:has(.diagram) {
                break-inside: auto;
                page-break-inside: auto;
            }

            .diagram,
            .diagram .mermaid,
            .diagram .bassist-mermaid {
                break-inside: avoid;
                page-break-inside: avoid;
                overflow: hidden;
                max-width: 100%;
            }

            .diagram svg {
                break-inside: avoid;
                page-break-inside: avoid;
                max-width: 100% !important;
                max-height: 230mm !important;
                display: block;
                margin-inline: auto;
            }

            .artifact--compact-diagram {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .diagram--compact {
                max-width: 88mm;
                margin-inline: auto;
            }

            .diagram--compact svg {
                max-width: 88mm !important;
                max-height: 140mm !important;
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
    
        /* Open comments in printed documents (#8): Word-style margin balloons */
        .print-pack.has-comment-margin { max-width: 1200px; padding-right: 290px; }
        .has-comment-margin .artifact { position: relative; }
        .print-comments {
            float: right; clear: right; position: relative;
            width: 260px; margin: 0 -290px .5rem 0; padding: 0;
            font: 11.5px/1.4 system-ui, -apple-system, "Segoe UI", sans-serif; color: #3f2d0c;
            break-inside: avoid;
        }
        .print-comments::before {
            content: ""; position: absolute; top: .9rem; right: 100%; width: 30px;
            border-top: 1px dashed #d97706;
        }
        .print-comments__thread {
            background: #fff7e6; border: 1px solid #f5c26b; border-left: 3px solid #d97706;
            border-radius: 4px; padding: 6px 8px; box-shadow: 0 1px 2px rgba(0,0,0,.06);
        }
        .print-comments__thread + .print-comments__thread { margin-top: 6px; }
        .print-comments__line + .print-comments__line { margin-top: 5px; }
        .print-comments__line--reply { margin-left: 8px; padding-left: 6px; border-left: 2px solid #fcd34d; }
        .print-comments__who { display: flex; align-items: baseline; gap: 5px; flex-wrap: wrap; }
        .print-comments__num { font: 700 10px/1 ui-monospace, Consolas, monospace; color: #fff; background: #d97706; border-radius: 3px; padding: 2px 4px; }
        .print-comments__when { color: #92400e; font-size: 10px; }
        .print-comments__text { margin-top: 1px; overflow-wrap: anywhere; }
        .has-comment-margin .artifact:has(> .print-comments) .item-title { background: #fff3c4; box-decoration-break: clone; -webkit-box-decoration-break: clone; }
        @media print {
            .print-pack.has-comment-margin { max-width: none; padding-right: 62mm; }
            .print-comments { width: 56mm; margin-right: -62mm; font-size: 8pt; }
            .print-comments::before { width: 6mm; }
            .print-comments__thread { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .has-comment-margin .artifact:has(> .print-comments) .item-title { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .print-notice { margin: 0 0 1rem; padding: .625rem .875rem; border-radius: 8px; background: #fffbeb; border: 1px solid #fcd34d; color: #78350f; font: 600 13px/1.45 system-ui, -apple-system, "Segoe UI", sans-serif; }
        .print-appendix__table { width: 100%; border-collapse: collapse; font: 12.5px/1.45 system-ui, -apple-system, "Segoe UI", sans-serif; margin-top: .75rem; }
        .print-appendix__table th, .print-appendix__table td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; text-align: left; }
        .print-appendix__table th { background: #f8fafc; font-weight: 600; }
        .print-appendix__table tr { break-inside: avoid; }
        .ba-mention { font-weight: 600; color: #1b84ff; }
        .print-btn--on { border-color: #d97706 !important; color: #92400e !important; background: #fffbeb !important; }
    </style>
    @stack('styles')
</head>
<body>
    @yield('toolbar')

    <main class="print-pack @yield('pack-class')">
        @stack('print-notice')
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
