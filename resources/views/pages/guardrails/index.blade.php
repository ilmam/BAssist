@extends(ui_layout())

@section('main')
    <div class="space-y-5">
        <x-card title="{{ __('ui.guardrails') }}">
            <x-slot:titleAside>
                <x-help-trigger topic="guardrails" />
            </x-slot:titleAside>
            <p class="text-sm text-muted-foreground mb-5">{{ __('ui.guardrails_help') }}</p>

            @include('pages.partials.list-filter-form', [
                'listFilters' => $filters ?? [],
                'allowedListFilters' => $allowedListFilters ?? ['project_id'],
                'action' => $filterAction ?? route('guardrails.index'),
                'clearUrl' => $filterClearUrl ?? null,
            ])
        </x-card>

        @forelse ($sections as $section)
            @include('pages.partials.hub-entity-section', [
                'section' => $section,
                'emptyMessage' => __('ui.guardrails_empty'),
            ])
        @empty
            <x-card title="{{ __('ui.guardrails') }}">
                <p class="text-sm text-muted-foreground">{{ __('ui.guardrails_no_access') }}</p>
            </x-card>
        @endforelse
    </div>
@endsection
