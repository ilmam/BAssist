@extends(ui_layout())

@section('main')
    @php
        use App\Helpers\DatatableUi;
        use App\Helpers\ListUi;

        $preserve = ListUi::contextFilters($listFilters ?? []);
        $orphans = ListUi::orphansToggle(model_route($model, 'index'), $listFilters ?? []);

        // Title normally absorbs leftover space; give it a fixed share so
        // parent need / SN counts aren't crowded on this list.
        $columns = collect($columns)->map(function ($col) {
            if ($col !== 'title') {
                return $col;
            }

            return [
                'data' => 'title',
                'name' => 'title',
                'style' => DatatableUi::identityWidthStyle(),
            ];
        })->all();

        $relationColumns = [
            ListUi::relatedLinkColumn('primary_business_need_cell', [
                'title' => __('ui.business_need'),
            ]),
            ListUi::childLinkColumn('StakeholderNeed', 'business_objective_id', 'stakeholder_needs_count', [
                'title' => __('ui.stakeholder_needs'),
                'preserve' => $preserve,
            ]),
        ];

        $toolbarExtras = '<a href="'.e($orphans['url']).'" class="'.e(ui_btn_classes($orphans['active'] ? 'primary' : 'outline')).'">'
            .e(__('ui.show_orphans'))
            .'</a>';
    @endphp

    @include('pages.partials.entity-list', [
        'columns' => $columns,
        'listHelp' => __('ui.babok_step_future_state_objectives_note'),
    ])
@endsection

@push('styles')
    @include('pages.partials.orphan-row-styles')
@endpush
