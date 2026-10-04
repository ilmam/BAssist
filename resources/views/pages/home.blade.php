@extends(ui_layout())

@section('main')
    @php
        $hour = (int) now()->format('G');
        $greetingKey = $hour < 12 ? 'ui.home_greeting_morning' : ($hour < 18 ? 'ui.home_greeting_afternoon' : 'ui.home_greeting_evening');
        $firstName = \Illuminate\Support\Str::of((string) ($user?->name ?? ''))->before(' ')->toString();
        $canCreateProject = entity_can('Project', 'create');
    @endphp

    <div class="space-y-5">
        {{-- Hero --}}
        <div class="ba-home-hero">
            <div class="min-w-0">
                <h1 class="text-xl font-semibold text-foreground">{{ __($greetingKey) }}{{ $firstName !== '' ? ', '.$firstName : '' }}</h1>
                <p class="text-sm text-muted-foreground mt-1">{{ __('ui.home_subtitle') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($canCreateProject)
                    <x-button type="link" href="{{ model_route('Project', 'create') }}" icon="plus" color="primary"
                              class="js-open-modal" data-modal-url="{{ model_modal_path('Project', 'create') }}">{{ __('ui.home_new_project') }}</x-button>
                @endif
                <x-button type="link" href="{{ route('help.guide') }}" icon="book-open" color="light">{{ __('ui.home_babok_guide') }}</x-button>
            </div>
        </div>

        {{-- KPIs --}}
        @if ($kpis !== [])
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                @foreach ($kpis as $kpi)
                    <a href="{{ $kpi['url'] ?? '#' }}" class="ba-kpi ba-kpi--{{ $kpi['value'] > 0 ? $kpi['tone'] : 'neutral' }}" title="{{ $kpi['hint'] }}">
                        <span class="ba-kpi__icon"><i class="ki-filled ki-{{ $kpi['icon'] }}"></i></span>
                        <span class="min-w-0">
                            <span class="ba-kpi__value">{{ $kpi['value'] }}</span>
                            <span class="ba-kpi__label">{{ $kpi['label'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Projects with readiness --}}
        <x-card :title="__('ui.home_projects_title')">
            <x-slot:toolbar>
                @if ($projectTotal > $projects->count())
                    <x-button type="link" href="{{ model_route('Project', 'index') }}" color="light" size="sm" icon="arrow-right">
                        {{ __('ui.home_view_all_projects', ['count' => $projectTotal]) }}
                    </x-button>
                @endif
            </x-slot:toolbar>

            @if ($projects->isEmpty())
                <x-empty-state icon="abstract-26" :title="__('ui.home_no_projects_title')" :hint="__('ui.home_no_projects_hint')">
                    <ol class="ba-journey">
                        @foreach (config('navigation.hierarchy.project_folders', []) as $folder)
                            <li><i class="ki-filled ki-{{ $folder['icon'] ?? 'folder' }}"></i>{{ $folder['short'] ?? $folder['label'] }}</li>
                        @endforeach
                    </ol>
                    @if ($canCreateProject)
                        <x-slot:actions>
                            <x-button type="link" href="{{ model_route('Project', 'create') }}" icon="plus" color="primary"
                                      class="js-open-modal" data-modal-url="{{ model_modal_path('Project', 'create') }}">{{ __('ui.home_create_first_project') }}</x-button>
                        </x-slot:actions>
                    @endif
                </x-empty-state>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach ($projects as $row)
                        @php $project = $row['project']; @endphp
                        <a href="{{ route('projects.dashboard', $project) }}" class="ba-project-card">
                            <div class="flex items-start gap-3">
                                <x-progress-ring :pct="$row['score']" size="60" stroke="6" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <x-code-chip :code="$project->code" />
                                        <span class="font-semibold text-foreground truncate">{{ $project->name }}</span>
                                    </div>
                                    <div class="text-xs text-muted-foreground truncate mt-0.5">
                                        {{ $project->workspace?->name }}
                                        @if ($project->updated_at) · {{ __('ui.home_updated', ['when' => $project->updated_at->diffForHumans()]) }} @endif
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                        <x-status-badge :value="$project->status?->name" />
                                        @if ($row['critical'] > 0)
                                            <x-status-badge tone="danger">{{ trans_choice('ui.home_critical_gaps', $row['critical'], ['count' => $row['critical']]) }}</x-status-badge>
                                        @elseif ($row['gaps'] > 0)
                                            <x-status-badge tone="warning">{{ trans_choice('ui.readiness_folder_gaps', $row['gaps'], ['count' => $row['gaps']]) }}</x-status-badge>
                                        @else
                                            <x-status-badge tone="success">{{ __('ui.readiness_all_clear_short') }}</x-status-badge>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if ($row['folders'] !== [])
                                <div class="ba-project-card__folders">
                                    @foreach ($row['folders'] as $folder)
                                        @php $fpct = $folder['pct'] ?? 0; @endphp
                                        <div title="{{ $folder['label'] }}: {{ $folder['passing'] }}/{{ $folder['checks'] }}">
                                            <span class="ba-project-card__folder-label">{{ $folder['short'] }}</span>
                                            <span class="ba-mini-bar ba-mini-bar--{{ $fpct >= 80 ? 'success' : ($fpct >= 50 ? 'warning' : 'danger') }}"><span style="width: {{ $fpct }}%"></span></span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </x-card>

        @if ($reviews->isNotEmpty())
            <x-card :title="__('ui.home_reviews_title')">
                <p class="text-sm text-muted-foreground mb-2">{{ __('ui.home_reviews_hint') }}</p>
                <ul class="ba-item-list">
                    @foreach ($reviews as $item)
                        @include('pages.partials.home-item-row', ['item' => $item])
                    @endforeach
                </ul>
            </x-card>
        @endif

        @if ($mentions->isNotEmpty())
            <x-card :title="__('ui.home_mentions_title')">
                <ul class="ba-item-list">
                    @foreach ($mentions as $mention)
                        @php
                            $target = $mention->commentable;
                            $entity = class_basename($target);
                        @endphp
                        <li class="ba-item">
                            <span class="ba-item__icon" aria-hidden="true"><i class="ki-filled ki-messages"></i></span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2 min-w-0">
                                    <x-code-chip :code="$target->getAttribute('code')" :href="model_route($entity, 'show', $target->getKey())" :modal="model_modal_path($entity, 'view', $target->getKey())" data-modal-nav="off" />
                                    <a href="{{ model_route($entity, 'show', $target->getKey()) }}" class="ba-item__title js-open-modal" data-modal-url="{{ model_modal_path($entity, 'view', $target->getKey()) }}" data-modal-nav="off">{{ $target->getAttribute('title') ?? $target->getAttribute('name') }}</a>
                                </span>
                                <span class="ba-item__meta">{{ __('ui.home_mention_by', ['name' => $mention->author?->name ?? '—', 'when' => $mention->created_at?->diffForHumans()]) }} — “{{ \Illuminate\Support\Str::limit($mention->body, 90) }}”</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        <div class="ba-home-columns">
            {{-- Awaiting decision --}}
            @if (entity_can('ChangeRequest', 'view'))
                <x-card :title="__('ui.home_awaiting_title')">
                    @if ($awaiting->isEmpty())
                        <x-empty-state icon="check-circle" :title="__('ui.home_awaiting_empty_title')" :hint="__('ui.home_awaiting_empty_hint')" compact />
                    @else
                        <ul class="ba-item-list">
                            @foreach ($awaiting as $item)
                                @include('pages.partials.home-item-row', ['item' => $item])
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            @endif

            {{-- Recent activity --}}
            <x-card :title="__('ui.home_activity_title')">
                @if ($activity->isEmpty())
                    <x-empty-state icon="time" :title="__('ui.home_activity_empty_title')" :hint="__('ui.home_activity_empty_hint')" compact />
                @else
                    <ul class="ba-item-list">
                        @foreach ($activity as $item)
                            @include('pages.partials.home-item-row', ['item' => $item])
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
@endsection
