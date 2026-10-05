@extends(ui_layout())

@section('main')
    <div class="space-y-5">
        <x-card title="{{ $project->name }}">
            <x-slot:toolbar>
                <div class="flex flex-wrap items-center gap-2">
                    @if (entity_can('Project', 'update'))
                        <x-button
                            type="link"
                            href="{{ model_modal_path('Project', 'edit', $project->id) }}"
                            icon="pencil"
                            iconOnly="true"
                            color="light"
                            activeColor="primary"
                            class="js-open-modal"
                            data-modal-url="{{ model_modal_path('Project', 'edit', $project->id) }}"
                        ></x-button>
                    @endif
                    @php
                        $downloadMenuItems = [];
                        foreach (config('babok_documents.documents', []) as $docKey => $docMeta) {
                            $downloadMenuItems[] = [
                                'label' => __($docMeta['menu_title'] ?? $docMeta['title']),
                                'url' => route('projects.babok.show', [$project, $docKey]),
                                'target' => '_blank',
                            ];
                        }
                        $downloadMenuItems[] = [
                            'label' => __('ui.export_pack'),
                            'url' => route('projects.export', $project),
                            'target' => '_blank',
                        ];
                        $downloadMenuItems[] = [
                            'label' => __('ui.babok_documents'),
                            'url' => route('projects.babok.index', $project),
                        ];
                    @endphp
                    <div class="create-split-btn inline-flex items-stretch">
                        <x-button
                            type="link"
                            href="{{ route('projects.export', $project) }}"
                            icon="{{ entity_icon('export_pack') }}"
                            color="primary"
                            activeColor="primary"
                            class="create-split-btn__main"
                            target="_blank"
                        >
                            {{ __('ui.project_downloads') }}
                        </x-button>
                        <div class="inline-flex" data-kt-dropdown="true" data-kt-dropdown-trigger="click">
                            <button
                                type="button"
                                class="kt-btn kt-btn-icon kt-btn-primary create-split-btn__toggle"
                                data-kt-dropdown-toggle="true"
                                aria-label="{{ __('ui.project_downloads') }}"
                            >
                                <i class="ki-filled ki-down text-xs"></i>
                            </button>
                            <div class="kt-dropdown-menu min-w-[240px]" data-kt-dropdown-menu="true">
                                @foreach ($downloadMenuItems as $item)
                                    <a
                                        href="{{ $item['url'] }}"
                                        class="kt-dropdown-menu-link"
                                        @if (! empty($item['target'])) target="{{ $item['target'] }}" rel="noopener" @endif
                                        data-kt-dropdown-dismiss="true"
                                    >{{ $item['label'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </x-slot:toolbar>

            <div class="flex flex-wrap gap-2 mb-4">
                @if ($project->code)
                    <span class="kt-badge kt-badge-outline">{{ __('ui.code') }}: {{ $project->code }}</span>
                @endif
                @if ($project->workspace)
                    <span class="kt-badge kt-badge-outline">{{ __('ui.workspace') }}: {{ $project->workspace->name }}</span>
                @endif
                @if ($project->status)
                    <span class="kt-badge kt-badge-outline">{{ __('ui.status') }}: {{ $project->status->name }}</span>
                @endif
            </div>

            @if (filled($project->description))
                <p class="text-sm text-muted-foreground whitespace-pre-line">{{ $project->description }}</p>
            @else
                <p class="text-sm text-muted-foreground">{{ __('ui.project_dashboard_no_description') }}</p>
            @endif
        </x-card>

        @php
            $readinessItems = $readiness['items'] ?? [];
            $readinessSpine = $readiness['spine'] ?? [];
            $readinessScore = $readiness['score'] ?? null;
            $readinessFolders = array_values(array_filter($readiness['folders'] ?? [], fn ($f) => ($f['checks'] ?? 0) > 0));
            $readinessSeverity = $readiness['severity'] ?? ['critical' => 0, 'warn' => 0, 'info' => 0];
            $severityTone = ['critical' => 'danger', 'warn' => 'warning', 'info' => 'info'];
            $severityRank = ['critical' => 0, 'warn' => 1, 'info' => 2];
            $gapsByFolder = [];
            foreach ($readinessItems as $gap) {
                $gapsByFolder[$gap['folder'] ?? 'other'][] = $gap;
            }
            foreach ($gapsByFolder as &$folderGaps) {
                usort($folderGaps, fn ($a, $b) => [$severityRank[$a['severity']] ?? 9, -$a['count']] <=> [$severityRank[$b['severity']] ?? 9, -$b['count']]);
            }
            unset($folderGaps);
        @endphp

        <x-card :title="__('ui.project_readiness')">
            <x-slot:titleAside>
                <x-help-trigger topic="readiness" />
            </x-slot:titleAside>
            <x-slot:toolbar>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['critical', 'warn', 'info'] as $sev)
                        @if (($readinessSeverity[$sev] ?? 0) > 0)
                            <x-status-badge :tone="$severityTone[$sev]">{{ __('ui.readiness_severity_'.$sev) }} · {{ $readinessSeverity[$sev] }}</x-status-badge>
                        @endif
                    @endforeach
                </div>
            </x-slot:toolbar>

            <p class="text-sm text-muted-foreground mb-5">{{ __('ui.project_readiness_help') }}</p>

            {{-- Overall score + BABOK folder health --}}
            <div class="ba-readiness-grid">
                <div class="ba-readiness-score">
                    <x-progress-ring :pct="$readinessScore" size="96" stroke="9" />
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-foreground">{{ __('ui.readiness_score') }}</div>
                        <div class="text-xs text-muted-foreground mt-1">
                            {{ $readinessScore === null ? __('ui.readiness_score_empty') : __('ui.readiness_score_caption') }}
                        </div>
                        <div class="text-xs text-muted-foreground mt-2">{{ trans_choice('ui.readiness_folder_gaps', $readiness['total_gaps'] ?? 0, ['count' => $readiness['total_gaps'] ?? 0]) }}</div>
                    </div>
                </div>

                @foreach ($readinessFolders as $folder)
                    <a href="#readiness-{{ $folder['key'] }}" class="ba-folder-card">
                        <x-progress-ring :pct="$folder['pct']" size="52" stroke="6" />
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 text-sm font-semibold text-foreground">
                                <i class="ki-filled ki-{{ $folder['icon'] }} text-muted-foreground"></i>
                                <span class="truncate">{{ $folder['short'] }}</span>
                            </div>
                            @if ($folder['babok'])
                                <div class="text-[11px] text-muted-foreground truncate" title="{{ $folder['babok'] }}">{{ $folder['babok'] }}</div>
                            @endif
                            <div class="mt-1.5">
                                @if ($folder['gaps'] === 0)
                                    <x-status-badge tone="success">{{ __('ui.readiness_all_clear_short') }}</x-status-badge>
                                @else
                                    <x-status-badge :tone="$folder['critical'] > 0 ? 'danger' : 'warning'">{{ trans_choice('ui.readiness_folder_gaps', $folder['gaps'], ['count' => $folder['gaps']]) }}</x-status-badge>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Need spine coverage --}}
            @if ($readinessSpine !== [])
                <h4 class="ba-section-label">{{ __('ui.readiness_spine_heading') }}</h4>
                <ol class="ba-spine">
                    @foreach ($readinessSpine as $stage)
                        @php
                            $pct = $stage['pct'];
                            $tone = $pct === null ? 'neutral' : ($pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger'));
                        @endphp
                        <li>
                            <a href="{{ $stage['url'] ?? '#' }}" class="ba-spine__step ba-spine__step--{{ $tone }}">
                                <span class="ba-spine__label" title="{{ $stage['label'] }}">{{ $stage['label'] }}</span>
                                <span class="ba-spine__value">{{ $pct === null ? '—' : $pct.'%' }}</span>
                                <span class="ba-spine__meta">{{ __('ui.readiness_ready_of_total', ['ready' => $stage['ready'], 'total' => $stage['total']]) }}</span>
                                <span class="ba-spine__bar"><span style="width: {{ $pct ?? 0 }}%"></span></span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            @endif

            {{-- Gaps, grouped by BABOK folder, each with a fix action --}}
            @if ($readinessItems === [])
                <x-empty-state icon="verify" :title="__('ui.readiness_all_clear_title')" :hint="__('ui.readiness_all_clear')" compact />
            @else
                <div>
                    <h4 class="ba-section-label">{{ __('ui.readiness_gaps_heading') }}</h4>
                    @foreach ($readiness['folders'] ?? [] as $folder)
                        @continue(empty($gapsByFolder[$folder['key']]))
                        <section id="readiness-{{ $folder['key'] }}" class="ba-folder-section">
                            <h4 class="ba-folder-section__title">
                                <i class="ki-filled ki-{{ $folder['icon'] }} text-muted-foreground"></i>
                                {{ $folder['label'] }}
                            </h4>
                            <ul class="ba-gap-list">
                                @foreach ($gapsByFolder[$folder['key']] as $gap)
                                    <li class="ba-gap">
                                        <x-status-badge :tone="$severityTone[$gap['severity']] ?? 'neutral'" class="ba-gap__sev">{{ __('ui.readiness_severity_'.$gap['severity']) }}</x-status-badge>
                                        <span class="ba-gap__label">{{ $gap['label'] }}</span>
                                        <span class="ba-gap__count" title="{{ __('ui.readiness_affected_items') }}">{{ $gap['count'] }}</span>
                                        <span class="ba-gap__actions">
                                            @if (! empty($gap['fix_modal']))
                                                <x-button type="link" href="{{ $gap['url'] ?? '#' }}" icon="plus" color="primary" size="sm"
                                                          class="js-open-modal" data-modal-url="{{ $gap['fix_modal'] }}">{{ __('ui.readiness_fix_add') }}</x-button>
                                            @elseif (! empty($gap['url']))
                                                <x-button type="link" href="{{ $gap['url'] }}" icon="arrow-right" color="light" size="sm">{{ __('ui.readiness_fix_review') }}</x-button>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                    @if (! empty($gapsByFolder['other']))
                        <ul class="ba-gap-list">
                            @foreach ($gapsByFolder['other'] as $gap)
                                <li class="ba-gap">
                                    <x-status-badge :tone="$severityTone[$gap['severity']] ?? 'neutral'" class="ba-gap__sev">{{ __('ui.readiness_severity_'.$gap['severity']) }}</x-status-badge>
                                    <span class="ba-gap__label">{{ $gap['label'] }}</span>
                                    <span class="ba-gap__count">{{ $gap['count'] }}</span>
                                    <span class="ba-gap__actions">
                                        @if (! empty($gap['url']))
                                            <x-button type="link" href="{{ $gap['url'] }}" icon="arrow-right" color="light" size="sm">{{ __('ui.readiness_fix_review') }}</x-button>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </x-card>

        {{-- Project-wide comments: remarks and findings that belong to no single record. --}}
        @if ($commentThreads !== null)
            @include('pages.partials.comments', [
                'commentModel' => 'Project',
                'commentRecordId' => (int) $project->id,
                'threads' => $commentThreads,
                'mentionUsers' => $mentionUsers,
            ])
        @endif

        @if ($counts !== [])
            <div>
                <h3 class="text-sm font-medium text-foreground mb-3">{{ __('ui.project_dashboard_summary') }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                    @foreach ($counts as $item)
                        <a href="{{ $item['url'] }}" class="kt-card hover:border-primary transition-colors block">
                            <div class="kt-card-body p-4 flex items-center gap-3">
                                <i class="ki-filled ki-{{ $item['icon'] }} text-xl text-primary"></i>
                                <div class="min-w-0">
                                    <div class="text-2xl font-semibold leading-none">{{ $item['count'] }}</div>
                                    <div class="text-xs text-muted-foreground mt-1 truncate">{{ $item['label'] }}</div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <x-card title="{{ __('ui.project_dashboard_quick_links') }}">
            <div class="flex flex-wrap gap-2">
                @foreach ($links as $link)
                    <x-button
                        type="link"
                        href="{{ $link['url'] }}"
                        icon="{{ $link['icon'] }}"
                        color="light"
                        activeColor="primary"
                        :target="! empty($link['external']) ? '_blank' : null"
                    >
                        {{ $link['label'] }}
                    </x-button>
                @endforeach
            </div>
        </x-card>

        <div>
            <x-button
                type="link"
                href="{{ model_route('Project', 'index').'?'.http_build_query(['workspace_id' => $project->workspace_id]) }}"
                color="light"
            >
                {{ __('ui.back_to_projects') }}
            </x-button>
        </div>
    </div>
@endsection
