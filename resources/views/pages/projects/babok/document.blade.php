@extends('layouts.print')

@php
    $pack = $pack ?? [];
    $strategic_baseline = $pack['strategic_baseline'] ?? null;
    $scope_items = $pack['scope_items'] ?? collect();
    $objectives = $pack['objectives'] ?? collect();
    $needs = $pack['needs'] ?? collect();
    $stakeholders = $pack['stakeholders'] ?? collect();
    $stakeholder_needs = $pack['stakeholder_needs'] ?? collect();
    $state_flows = $pack['state_flows'] ?? [];
    $swimlane_flows = $pack['swimlane_flows'] ?? [];
    $architecture = $pack['architecture'] ?? null;
    $assumptions = $pack['assumptions'] ?? collect();
    $constraints = $pack['constraints'] ?? collect();
    $business_rules = $pack['business_rules'] ?? collect();
    $risks = $pack['risks'] ?? collect();
    $functional_requirements = $pack['functional_requirements'] ?? collect();
    $features = $pack['features'] ?? [];
    $matrix = $pack['matrix'] ?? ['rows' => [], 'summary' => []];
    $readiness = $pack['readiness'] ?? ['items' => [], 'total_gaps' => 0];
    $change_requests = $change_requests ?? collect();
    $sections = $document['sections'] ?? [];
@endphp

@section('title', ($project->name ?? __('ui.project')).' — '.$document['title'])

@section('toolbar')
    <div class="print-toolbar no-print">
        <p class="print-toolbar__hint">{{ __('ui.project_export_print_hint') }}</p>
        <div class="print-toolbar__actions">
            <a class="print-btn" href="{{ route('projects.babok.index', $project) }}">{{ __('ui.babok_documents') }}</a>
            <a class="print-btn" href="{{ route('projects.export', $project) }}">{{ __('ui.export_pack') }}</a>
            <button type="button" class="print-btn print-btn--primary" data-print-pack>
                {{ __('ui.print_to_pdf') }}
            </button>
        </div>
    </div>
@endsection

@section('content')
    <header class="cover">
        <p class="cover__eyebrow">{{ __('ui.babok_documents') }}</p>
        <h1>{{ $document['title'] }}</h1>
        <p class="cover__description">{{ $document['purpose'] }}</p>
        <div class="cover__meta">
            <div>
                <strong>{{ __('ui.project') }}</strong>
                {{ $project->name }}
            </div>
            <div>
                <strong>{{ __('ui.code') }}</strong>
                {{ $project->code ?: '—' }}
            </div>
            <div>
                <strong>{{ __('ui.babok_reference') }}</strong>
                {{ $document['babok'] }}
            </div>
            <div>
                <strong>{{ __('ui.generated_at') }}</strong>
                {{ $generated_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
            </div>
        </div>
        @if (($omitted_orphans ?? 0) > 0)
            <p class="muted" style="margin-top: 1rem; font: 13px/1.45 system-ui, sans-serif;">
                {{ __('ui.babok_omitted_orphans', ['count' => $omitted_orphans]) }}
            </p>
        @endif
    </header>

    @if (count($sections) > 1)
        <nav class="export-toc" aria-label="{{ __('ui.table_of_contents') }}">
            <h2 class="section-title">{{ __('ui.table_of_contents') }}</h2>
            <ol class="export-toc__list">
                @foreach ($sections as $section)
                    <li>
                        <a href="#section-{{ $section['key'] }}">{{ $section['heading'] }}</a>
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    @foreach ($sections as $index => $section)
        <header id="section-{{ $section['key'] }}" class="section-banner @if ($index > 0) section-banner--break @endif">
            <h2 class="section-title" style="margin-bottom: 0;">{{ $section['heading'] }}</h2>
        </header>
        @include('pages.projects.babok.partials.'.$section['partial'])
    @endforeach
@endsection

@push('styles')
    <style>
        .export-toc {
            margin: 0 0 1.5rem;
        }

        .export-toc__list {
            margin: 0;
            padding: 0 0 0 1.25rem;
            font: 14px/1.55 system-ui, sans-serif;
            color: var(--ink);
        }

        .export-toc__list li {
            margin: 0.35rem 0;
        }

        .export-toc__list a {
            color: var(--ink);
            text-decoration: underline;
            text-underline-offset: 0.15em;
        }

        .section-banner {
            margin-top: 0.5rem;
            padding-bottom: 0.25rem;
            border-bottom: 1px solid var(--line);
            margin-bottom: 1rem;
        }

        .gherkin-print {
            margin: 0.75rem 0 0;
            padding: 1rem 1.1rem;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            font: 12px/1.55 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            white-space: pre-wrap;
            word-break: break-word;
            color: #111;
        }

        .strategic-baseline .kv {
            display: flex;
            flex-direction: column;
            gap: 0;
            margin: 0;
        }

        .strategic-baseline .kv dt {
            margin: 1.35rem 0 0;
            padding-top: 1.1rem;
            border-top: 1px solid #ececec;
            font-weight: 600;
            color: var(--muted);
        }

        .strategic-baseline .kv dt:first-child {
            margin-top: 0;
            padding-top: 0;
            border-top: 0;
        }

        .strategic-baseline .kv dd {
            margin: 0.45rem 0 0;
        }

        table.scope-columns th,
        table.scope-columns td {
            width: 50%;
        }

        .risk-score-chip {
            display: inline-block;
            margin-left: 0.5rem;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            font: 600 11px/1.3 system-ui, sans-serif;
            vertical-align: middle;
            background: #eef2f6;
            color: #4b5675;
        }

        .risk-score-chip--high {
            background: #fff3e0;
            color: #b54708;
        }

        .risk-score-chip--critical {
            background: #fee4e2;
            color: #b42318;
        }

        .artifact--risk-high {
            border-left: 3px solid #f79009;
            padding-left: 0.75rem;
            background: #fffcf5;
        }

        .artifact--risk-critical {
            border-left: 3px solid #f04438;
            padding-left: 0.75rem;
            background: #fffbfa;
        }

        .artifact--coverage-gap {
            outline: 1px dashed #f04438;
            outline-offset: 2px;
        }

        .risk-gap-flag {
            margin: 0.75rem 0 0;
            font: 600 12px/1.4 system-ui, sans-serif;
            color: #b42318;
        }

        @media print {
            .export-toc__list a {
                text-decoration: none;
            }

            .gherkin-print {
                border: 0;
                border-radius: 0;
                padding: 0;
                font-size: 10.5pt;
            }

            .section-banner--break {
                break-before: page;
                page-break-before: always;
            }

            h2.section-title.section-title--break {
                break-before: page;
                page-break-before: always;
            }

            .artifact--risk-critical,
            .risk-score-chip--critical {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
@endpush

@push('scripts')
    @vite(['resources/js/project-export-print.js'])
@endpush
