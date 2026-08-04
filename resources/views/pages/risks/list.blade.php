@extends(ui_layout())

@section('main')
    @php
        use App\Helpers\DatatableUi;

        $listColumns = collect($columns)->map(function ($col) {
            if ($col !== 'score_label') {
                return $col;
            }

            return [
                'custom' => true,
                'name' => 'score_label',
                'title' => __('ui.score_label'),
                'style' => DatatableUi::compactStyle(),
                'template' => '<span class="risk-list-score risk-list-score--{score_band}">{score_label}</span>',
                'fields' => ['score_label', 'score_band'],
            ];
        })->all();
    @endphp

    @include('pages.partials.entity-list', [
        'dto' => $dto,
        'model' => $model,
        'columns' => $listColumns,
        'listFilters' => $listFilters ?? [],
        'allowedListFilters' => $allowedListFilters ?? [],
        'listHelp' => __('ui.babok_step_risk_assessment_note'),
        'datatableOptions' => [
            'rowClassField' => 'is_critical',
            'rowClass' => 'is-critical-risk-row',
        ],
    ])
@endsection

@push('styles')
    @include('pages.partials.critical-risk-row-styles')
@endpush
