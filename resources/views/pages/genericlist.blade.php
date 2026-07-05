@extends(ui_layout())

@section('main')
    @php
        $data = ArrayHelper::squash_array(array: $dto, withPrefix: true, onlyHeaders: false);
        $columns = array_keys($data);

        $options = [
            'columns' => $columns,
            'keys' => ['id', 'id'],
            'tableClass' => 'table-hover table-striped',
            'dataRoute' => 'api.'.Str::snake($model).'.index',
            'model' => $model,
            'dataRoutParameters' => ['modelName' => $model],
        ];
    @endphp

    <x-card title="{{ $model }} List">
        <x-slot:toolbar>
            <x-button type="link" href="{{ model_route_name($model, 'create') }}" icon="plus" iconOnly="true" color="primary" activeColor="primary"></x-button>
        </x-slot>
        <x-datatable :options="$options" :defaultButtons="true" />
    </x-card>
@endsection
