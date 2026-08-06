@extends(ui_layout())

@section('main')
    @php
        use App\Helpers\DatatableUi;
        use App\Helpers\ListUi;

        $preserve = ListUi::contextFilters($listFilters ?? []);

        // Name absorbs leftover by default; cap it so Project/Status/counts breathe.
        $columns = collect($columns)->map(function ($col) {
            if ($col !== 'name') {
                return $col;
            }

            return [
                'data' => 'name',
                'name' => 'name',
                'style' => DatatableUi::identityWidthStyle(),
            ];
        })->all();

        $relationColumns = [
            ListUi::childLinkColumn('StakeholderNeed', 'stakeholder_id', 'stakeholder_needs_count', [
                'title' => __('ui.stakeholder_needs'),
                'preserve' => $preserve,
            ]),
        ];
    @endphp

    @include('pages.partials.entity-list', [
        'columns' => $columns,
        'listHelp' => __('ui.babok_doc_stakeholder_engagement_note'),
    ])
@endsection

@push('styles')
    @include('pages.partials.orphan-row-styles')
@endpush
