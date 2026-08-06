@extends(ui_layout())

@section('main')
    @php
        $queryBase = array_filter([
            'project_id' => $filters['project_id'] ?? null,
            'workspace_id' => $filters['workspace_id'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
    @endphp

    <div class="space-y-5">
        <x-card title="{{ __('ui.guardrails') }}">
            <x-slot:titleAside>
                <x-help-trigger topic="guardrails" />
            </x-slot:titleAside>
            <p class="text-sm text-muted-foreground mb-5">{{ __('ui.guardrails_help') }}</p>

            <form method="GET" action="{{ route('guardrails.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="flex flex-col gap-1 min-w-[220px]">
                    <label for="project_id" class="text-sm text-muted-foreground">{{ __('ui.project') }}</label>
                    <select name="project_id" id="project_id" class="kt-select" data-kt-select="true">
                        <option value="">{{ __('ui.all_projects') }}</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((int) ($filters['project_id'] ?? 0) === (int) $project->id)>
                                {{ $project->name }}@if ($project->code) ({{ $project->code }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>

                @if (! empty($filters['workspace_id']))
                    <input type="hidden" name="workspace_id" value="{{ $filters['workspace_id'] }}">
                @endif

                <x-button type="submit" color="primary">{{ __('ui.apply_filters') }}</x-button>

                @if (! empty($filters['project_id']))
                    <a href="{{ route('guardrails.index', array_filter(['workspace_id' => $filters['workspace_id'] ?? null])) }}"
                       class="kt-btn kt-btn-ghost">
                        {{ __('ui.clear_project') }}
                    </a>
                @endif
            </form>
        </x-card>

        @forelse ($sections as $section)
            <x-card :title="$section['label']">
                <x-slot:titleAside>
                    <x-help-trigger :model="$section['model']" />
                </x-slot:titleAside>
                <x-slot:toolbar>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="kt-badge kt-badge-outline">{{ $section['count'] }}</span>
                        <x-button type="link" href="{{ $section['index_url'] }}" color="ghost" size="sm" activeColor="primary">
                            {{ __('ui.view_all') }}
                        </x-button>
                        @if ($section['can_create'] && $section['create_modal_url'])
                            <x-button
                                type="link"
                                href="{{ $section['create_modal_url'] }}"
                                icon="plus"
                                iconOnly="true"
                                color="primary"
                                activeColor="primary"
                                class="js-open-modal"
                                data-modal-url="{{ $section['create_modal_url'] }}"
                            ></x-button>
                        @endif
                    </div>
                </x-slot:toolbar>

                <div class="flex items-start gap-3 mb-4">
                    <i class="ki-filled ki-{{ $section['icon'] }} text-2xl text-primary shrink-0"></i>
                    <p class="text-sm text-muted-foreground">{{ $section['description'] }}</p>
                </div>

                <div class="kt-scrollable-x-auto">
                    <table class="kt-table table-fixed">
                        <thead>
                            <tr>
                                <th class="min-w-[220px]">{{ __('ui.title') }}</th>
                                <th class="min-w-[160px]">{{ __('ui.project') }}</th>
                                <th class="min-w-[120px]">{{ __('ui.status') }}</th>
                                <th class="min-w-[100px] text-end">{{ __('ui.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($section['items'] as $item)
                                <tr>
                                    <td>
                                        <a href="{{ model_route($section['model'], 'show', $item->id) }}" class="text-primary font-medium hover:underline">
                                            {{ $item->title }}
                                        </a>
                                    </td>
                                    <td class="text-secondary-foreground">
                                        {{ $item->project?->name ?? '—' }}
                                    </td>
                                    <td class="text-secondary-foreground">
                                        {{ $item->status_label ?? $item->status }}
                                    </td>
                                    <td class="text-end">
                                        <div class="inline-flex items-center gap-1 justify-end">
                                            <x-button
                                                type="link"
                                                href="{{ model_route($section['model'], 'show', $item->id) }}"
                                                icon="eye"
                                                iconOnly="true"
                                                color="light"
                                                activeColor="primary"
                                            ></x-button>
                                            @if (entity_can($section['model'], 'update'))
                                                <x-button
                                                    type="link"
                                                    href="{{ model_modal_path($section['model'], 'edit', $item->id) }}"
                                                    icon="pencil"
                                                    iconOnly="true"
                                                    color="light"
                                                    activeColor="primary"
                                                    class="js-open-modal"
                                                    data-modal-url="{{ model_modal_path($section['model'], 'edit', $item->id) }}"
                                                ></x-button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-secondary-foreground">{{ __('ui.guardrails_empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        @empty
            <x-card title="{{ __('ui.guardrails') }}">
                <p class="text-sm text-muted-foreground">{{ __('ui.guardrails_no_access') }}</p>
            </x-card>
        @endforelse
    </div>
@endsection
