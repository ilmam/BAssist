@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
    @endphp

    <x-record-view :model="$model" :dto="$dto">
        @include('pages.state_flows.partials.view-content', [
            'dto' => $dto,
            'model' => $model,
            'fields' => $fields,
        ])
    </x-record-view>
@endsection
