@extends('layouts.print')

@section('title', ($feature->code ? $feature->code.' — ' : '').$feature->title.' — '.__('ui.print_feature'))

@section('toolbar')
    <div class="print-toolbar no-print">
        <p class="print-toolbar__hint">{{ __('ui.feature_print_hint') }}</p>
        <div class="print-toolbar__actions">
            <a class="print-btn" href="{{ $backUrl }}">{{ __('ui.back_to_feature') }}</a>
            @if (! empty($exportUrl))
                <a class="print-btn" href="{{ $exportUrl }}">{{ __('ui.download_feature') }}</a>
            @endif
            <button type="button" class="print-btn print-btn--primary" data-print-pack>
                {{ __('ui.print_to_pdf') }}
            </button>
        </div>
    </div>
@endsection

@section('content')
    <header class="cover">
        <p class="cover__eyebrow">{{ __('ui.feature_as_gherkin') }}</p>
        <h1>{{ $feature->title }}</h1>
        <div class="cover__meta">
            <div>
                <strong>{{ __('ui.code') }}</strong>
                {{ $feature->code ?: '—' }}
            </div>
            <div>
                <strong>{{ __('ui.project') }}</strong>
                {{ $feature->project?->name ?: '—' }}
            </div>
            <div>
                <strong>{{ __('ui.stakeholder_need') }}</strong>
                @if ($feature->stakeholderNeed)
                    {{ $feature->stakeholderNeed->code ? $feature->stakeholderNeed->code.' — ' : '' }}{{ $feature->stakeholderNeed->title }}
                @else
                    —
                @endif
            </div>
            <div>
                <strong>{{ __('ui.file') }}</strong>
                {{ $filename }}
            </div>
        </div>
    </header>

    <pre class="gherkin-print">{{ $gherkin }}</pre>
@endsection

@push('styles')
    <style>
        .gherkin-print {
            margin: 0;
            padding: 1rem 1.1rem;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            font: 12px/1.55 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            white-space: pre-wrap;
            word-break: break-word;
            color: #111;
            page-break-inside: auto;
        }

        @media print {
            .cover {
                margin-bottom: 1rem;
                padding-bottom: 0.75rem;
            }

            .gherkin-print {
                border: 0;
                border-radius: 0;
                padding: 0;
                font-size: 10.5pt;
            }
        }
    </style>
@endpush

@push('scripts')
    @vite(['resources/js/project-export-print.js'])
@endpush
