@extends(ui_layout())

@section('main')
    @php
        use App\Helpers\DatatableUi;
        use App\Helpers\ListUi;

        $preserve = ListUi::contextFilters($listFilters ?? []);
        $orphans = ListUi::orphansToggle(model_route($model, 'index'), $listFilters ?? []);

        // Title normally absorbs leftover space; give it a fixed share so
        // project / counts aren't crowded on this list.
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
            ListUi::childLinkColumn('BusinessObjective', 'business_need_id', 'business_objectives_count', [
                'title' => __('ui.business_objectives'),
                'preserve' => $preserve,
            ]),
        ];

        $toolbarExtras = '<a href="'.e($orphans['url']).'" class="'.e(ui_btn_classes($orphans['active'] ? 'primary' : 'outline')).'">'
            .e(__('ui.show_orphans'))
            .'</a>';
    @endphp

    @include('pages.partials.entity-list', [
        'columns' => $columns,
        'listHelp' => __('ui.babok_step_current_state_needs_note'),
    ])
@endsection

@push('styles')
    @include('pages.partials.orphan-row-styles')
@endpush
