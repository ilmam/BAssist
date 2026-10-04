@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
        $backUrl = ! empty($scenario->feature_id) ? model_route('Feature', 'show', $scenario->feature_id) : null;
        $backLabel = ! empty($scenario->feature_id) ? __('ui.back_to_feature') : null;
    @endphp

    <x-record-view :model="$model" :dto="$dto" :title="$scenario->gherkinKeyword().': '.$dto->title" :back-url="$backUrl" :back-label="$backLabel">
        @include('pages.scenarios.partials.view-content', [
            'dto' => $dto,
            'model' => $model,
            'fields' => $fields,
            'scenario' => $scenario,
            'gherkin' => $gherkin,
            'tagList' => $tagList ?? [],
            'cascade' => $cascade ?? null,
        ])
    </x-record-view>
@endsection
