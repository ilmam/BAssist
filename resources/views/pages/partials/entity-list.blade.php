@php
    use App\Helpers\ListUi;
    use Illuminate\Support\Str;

    $listFilters = $listFilters ?? [];
    $allowedListFilters = $allowedListFilters ?? [];
    $relationColumns = $relationColumns ?? [];
    $listHelp = $listHelp ?? null;
    $contextFilters = ListUi::contextFilters($listFilters);
    $indexUrl = model_route($model, 'index');
    $filterChips = ListUi::activeFilters($listFilters, $allowedListFilters, $indexUrl);

    // Clear-all drops relation/orphan filters but keeps sticky workspace/project scope.
    $clearAllUrl = $contextFilters === []
        ? $indexUrl
        : $indexUrl.'?'.http_build_query($contextFilters);

    $ajaxUrl = route('api.'.Str::snake($model).'.index', ['modelName' => $model]);
    if ($listFilters !== []) {
        $ajaxUrl .= (str_contains($ajaxUrl, '?') ? '&' : '?').http_build_query($listFilters);
    }

    $options = array_merge([
        'columns' => array_merge($columns, $relationColumns),
        'keys' => ['id'],
        'tableClass' => 'table-hover table-striped',
        'dataRoute' => 'api.'.Str::snake($model).'.index',
        'model' => $model,
        'dataRoutParameters' => ['modelName' => $model],
        'ajaxUrl' => $ajaxUrl,
    ], $datatableOptions ?? []);
@endphp

<x-card title="{{ $model }} List">
    <x-slot:titleAside>
        <x-help-trigger :model="$model" />
    </x-slot:titleAside>
    <x-slot:toolbar>
        <div class="flex items-center gap-2">
            @isset($toolbarExtras)
                {!! $toolbarExtras !!}
            @endisset
            @include('pages.partials.create-toolbar-button', ['model' => $model])
        </div>
    </x-slot>

    @if (filled($listHelp))
        <p class="text-sm text-muted-foreground mb-4">{{ $listHelp }}</p>
    @endif

    @include('pages.partials.list-filter-banner', [
        'filterChips' => $filterChips,
        'model' => $model,
        'clearAllUrl' => $clearAllUrl,
    ])

    <x-datatable :options="$options" :defaultButtons="true" />
</x-card>
