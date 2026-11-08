@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
    @endphp

    <x-record-view :model="$model" :dto="$dto">
        <div class="space-y-6">
            <x-details-view
                model="{{ $modelName }}"
                :dto="$dto"
                :fields="$fields" />

            @include('pages.briefs.partials.projects', ['briefId' => (int) $dto->id])
        </div>
    </x-record-view>
@endsection
