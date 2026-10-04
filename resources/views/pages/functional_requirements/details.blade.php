@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
    @endphp

    <x-record-view :model="$model" :dto="$dto">
        <div class="space-y-6">
            @include('pages.partials.spine-cascade', ['cascade' => $cascade ?? null, 'part' => 'before'])
            <x-details-view
                model="{{ $modelName }}"
                :dto="$dto"
                :fields="$fields">
            @include('pages.partials.spine-cascade', ['cascade' => $cascade ?? null, 'part' => 'after'])
            </x-details-view>
        </div>
    </x-record-view>
@endsection
