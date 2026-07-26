@extends(ui_layout())

@section('main')
    @php
        use Illuminate\Support\Str;

        $listFilters = $listFilters ?? [];
        $ajaxUrl = route('api.'.Str::snake($model).'.index', ['modelName' => $model]);
        if ($listFilters !== []) {
            $ajaxUrl .= (str_contains($ajaxUrl, '?') ? '&' : '?').http_build_query($listFilters);
        }

        $options = array_merge([
            'columns' => $columns,
            'keys' => ['id'],
            'tableClass' => 'table-hover table-striped',
            'dataRoute' => 'api.'.Str::snake($model).'.index',
            'model' => $model,
            'dataRoutParameters' => ['modelName' => $model],
            'ajaxUrl' => $ajaxUrl,
        ], $datatableOptions ?? []);
    @endphp

    <x-card title="{{ $model }} List">
        <x-slot:toolbar>
            @include('pages.partials.create-toolbar-button', ['model' => $model])
        </x-slot>
        <x-datatable :options="$options" :defaultButtons="true" />
    </x-card>
@endsection
