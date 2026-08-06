@php
    use App\Helpers\ListUi;

    $listFilters = $listFilters ?? [];
    $allowedListFilters = $allowedListFilters ?? [];
    $action = $action ?? model_route($model, 'index');
    $clearUrl = $clearUrl ?? null;
    $fields = ListUi::filterFormFields($allowedListFilters, $listFilters);
    $activeCount = count(ListUi::activeFilters($listFilters, $allowedListFilters, $action));
@endphp

@if ($fields !== [])
    <x-list-filter-panel :active-count="$activeCount" :clear-url="$clearUrl">
        <form method="GET" action="{{ $action }}" class="list-filter-panel__form" data-list-filter-form>
            @include('pages.partials.list-filter-fields', ['fields' => $fields])

            <div class="list-filter-panel__actions">
                <x-button type="submit" color="primary" activeColor="primary">
                    {{ __('ui.apply_filters') }}
                </x-button>
            </div>
        </form>
    </x-list-filter-panel>
@endif
