@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
    @endphp

    <x-record-view :model="$model" :dto="$dto" :page-only="true">
        <div class="space-y-6">
            <x-details-view model="{{ $modelName }}" :dto="$dto" :fields="$fields" :columns="2" />

            @include('pages.screens.partials.mockup', ['salt' => $dto->salt])

            @include('pages.screens.partials.elements', ['screen' => $screen])
        </div>
    </x-record-view>
@endsection
