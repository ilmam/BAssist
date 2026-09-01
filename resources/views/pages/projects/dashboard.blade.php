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
            $readinessSeverity = $readiness['severity'] ?? ['critical' => 0, 'warn' => 0, 'info' => 0];
            $readinessGrouped = ['critical' => [], 'warn' => [], 'info' => []];
            foreach ($readinessItems as $gap) {
                $readinessGrouped[$gap['severity']][] = $gap;
            }
            $scoreTone = $readinessScore === null
                ? 'text-muted-foreground'
                : ($readinessScore >= 80 ? 'text-success' : ($readinessScore >= 50 ? 'text-warning' : 'text-destructive'));
        @endphp

        <x-card :title="__('ui.project_readiness')">
            <x-slot:titleAside>
                <x-help-trigger topic="readiness" />
            </x-slot:titleAside>
            <x-slot:toolbar>
                <div class="flex flex-wrap items-center gap-2">
                    @if (($readinessSeverity['critical'] ?? 0) > 0)
                        <span class="kt-badge kt-badge-sm kt-badge-warning">{{ __('ui.readiness_severity_critical') }} {{ $readinessSeverity['critical'] }}</span>
                    @endif
                    @if (($readinessSeverity['warn'] ?? 0) > 0)
                        <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-warning">{{ __('ui.readiness_severity_warn') }} {{ $readinessSeverity['warn'] }}</span>
                    @endif
                    @if (($readinessSeverity['info'] ?? 0) > 0)
                        <span class="kt-badge kt-badge-sm kt-badge-outline">{{ __('ui.readiness_severity_info') }} {{ $readinessSeverity['info'] }}</span>
                    @endif
                    <span class="kt-badge kt-badge-outline">
                        {{ __('ui.readiness_gap_count', ['count' => $readiness['total_gaps'] ?? 0]) }}
                    </span>
                </div>
            </x-slot:toolbar>

            <p class="text-sm text-muted-foreground mb-5">{{ __('ui.project_readiness_help') }}</p>

            <div class="flex flex-col lg:flex-row gap-6 mb-6">
                <div class="shrink-0 text-center lg:text-start lg:w-40">
                    <div class="text-4xl font-semibold leading-none {{ $scoreTone }}">
                        {{ $readinessScore === null ? '—' : $readinessScore.'%' }}
                    </div>
                    <div class="text-xs text-muted-foreground mt-2">{{ __('ui.readiness_score') }}</div>
                    @if ($readinessScore === null)
                        <p class="text-xs text-muted-foreground mt-1">{{ __('ui.readiness_score_empty') }}</p>
                    @endif
                </div>

                @if ($readinessSpine !== [])
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 flex-1 min-w-0">
                        @foreach ($readinessSpine as $stage)
                            @php
                                $href = $stage['url'] ?? null;
                                $tag = $href ? 'a' : 'div';
                            @endphp
                            <{{ $tag }}
                                @if ($href) href="{{ $href }}" @endif
                                class="block rounded-lg border border-border p-3 {{ $href ? 'hover:border-primary transition-colors' : '' }}"
                            >
                                <div class="text-xs text-muted-foreground mb-1 truncate" title="{{ $stage['label'] }}">{{ $stage['label'] }}</div>
                                <div class="text-sm font-medium mb-2">
                                    {{ __('ui.readiness_ready_of_total', ['ready' => $stage['ready'], 'total' => $stage['total']]) }}
                                </div>
                                <div class="h-1.5 rounded-full bg-border overflow-hidden">
                                    <div
                                        class="h-full rounded-full {{ ($stage['pct'] ?? 0) >= 80 ? 'bg-success' : (($stage['pct'] ?? 0) >= 50 ? 'bg-warning' : 'bg-primary') }}"
                                        style="width: {{ $stage['pct'] ?? 0 }}%"
                                    ></div>
                                </div>
                            </{{ $tag }}>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($readinessItems === [])
                <p class="text-sm text-secondary-foreground">{{ __('ui.readiness_all_clear') }}</p>
            @else
                <h4 class="text-sm font-medium mb-3">{{ __('ui.readiness_gaps_heading') }}</h4>
                <div class="space-y-4">
                    @foreach (['critical' => 'readiness_severity_critical', 'warn' => 'readiness_severity_warn', 'info' => 'readiness_severity_info'] as $tone => $severityLabel)
                        @if ($readinessGrouped[$tone] !== [])
                            <div class="space-y-2">
                                <div class="text-xs font-medium text-muted-foreground">{{ __("ui.{$severityLabel}") }}</div>
                                @foreach ($readinessGrouped[$tone] as $gap)
                                    @php $gapTag = ! empty($gap['url']) ? 'a' : 'div'; @endphp
                                    <{{ $gapTag }}
                                        @if (! empty($gap['url'])) href="{{ $gap['url'] }}" @endif
                                        class="flex items-center justify-between gap-3 rounded-lg border border-border px-3 py-2.5 {{ ! empty($gap['url']) ? 'hover:border-primary transition-colors' : '' }}"
                                    >
                                        <span class="text-sm min-w-0">{{ $gap['label'] }}</span>
                                        <span class="text-sm font-semibold shrink-0">{{ $gap['count'] }}</span>
                                    </{{ $gapTag }}>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </x-card>

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
