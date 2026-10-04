@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
    @endphp

    <x-record-view :model="$model" :dto="$dto">
        @include('pages.swimlane_flows.partials.view-content', [
            'dto' => $dto,
            'model' => $model,
            'fields' => $fields,
            'stakeholderNeedOptions' => $stakeholderNeedOptions ?? [],
        ])
    </x-record-view>
@endsection
