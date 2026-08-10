@extends(ui_layout())

@section('main')
    <div class="space-y-5">
        <x-card title="{{ __('ui.solution_requirements') }}">
            <x-slot:titleAside>
                <x-help-trigger topic="solution_requirements" />
            </x-slot:titleAside>
            <p class="text-sm text-muted-foreground mb-5">{{ __('ui.solution_requirements_help') }}</p>

            @include('pages.partials.list-filter-form', [
                'listFilters' => $filters ?? [],
                'allowedListFilters' => $allowedListFilters ?? ['project_id'],
                'action' => $filterAction ?? route('solution_requirements.index'),
                'clearUrl' => $filterClearUrl ?? null,
            ])
        </x-card>

        @forelse ($sections as $section)
            @include('pages.partials.hub-entity-section', [
                'section' => $section,
                'emptyMessage' => __('ui.solution_requirements_empty'),
            ])
        @empty
            <x-card title="{{ __('ui.solution_requirements') }}">
                <p class="text-sm text-muted-foreground">{{ __('ui.solution_requirements_no_access') }}</p>
            </x-card>
        @endforelse
    </div>
@endsection
