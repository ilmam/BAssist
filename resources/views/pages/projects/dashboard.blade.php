@extends(ui_layout())

@section('main')
    <div class="space-y-5">
        <x-card title="{{ $project->name }}">
            <x-slot:titleAside>
                <x-help-trigger topic="projects" />
            </x-slot:titleAside>
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
                    <x-button
                        type="link"
                        href="{{ route('projects.babok.index', $project) }}"
                        icon="book"
                        color="light"
                        activeColor="primary"
                    >
                        {{ __('ui.babok_documents') }}
                    </x-button>
                    <x-button
                        type="link"
                        href="{{ route('projects.export', $project) }}"
                        icon="file-down"
                        color="primary"
                        activeColor="primary"
                        target="_blank"
                    >
                        {{ __('ui.export_pack') }}
                    </x-button>
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

        <x-card :title="__('ui.project_readiness')">
            <x-slot:toolbar>
                <span class="kt-badge kt-badge-outline">
                    {{ __('ui.readiness_gap_count', ['count' => $readiness['total_gaps'] ?? 0]) }}
                </span>
            </x-slot:toolbar>

            <p class="text-sm text-muted-foreground mb-4">{{ __('ui.project_readiness_help') }}</p>

            @if (($readiness['items'] ?? []) === [])
                <p class="text-sm text-secondary-foreground">{{ __('ui.readiness_all_clear') }}</p>
            @else
                <div class="kt-scrollable-x-auto">
                    <table class="kt-table">
                        <thead>
                            <tr>
                                <th>{{ __('ui.readiness_gap') }}</th>
                                <th class="w-24 text-end">{{ __('ui.count') }}</th>
                                <th class="w-28 text-end">{{ __('ui.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($readiness['items'] as $gap)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            @if ($gap['severity'] === 'critical')
                                                <span class="kt-badge kt-badge-sm kt-badge-warning">{{ __('ui.readiness_severity_critical') }}</span>
                                            @elseif ($gap['severity'] === 'warn')
                                                <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-warning">{{ __('ui.readiness_severity_warn') }}</span>
                                            @else
                                                <span class="kt-badge kt-badge-sm kt-badge-outline">{{ __('ui.readiness_severity_info') }}</span>
                                            @endif
                                            <span>{{ $gap['label'] }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end font-medium">{{ $gap['count'] }}</td>
                                    <td class="text-end">
                                        @if (! empty($gap['url']))
                                            <x-button type="link" href="{{ $gap['url'] }}" color="ghost" size="sm" activeColor="primary">
                                                {{ __('ui.view') }}
                                            </x-button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
