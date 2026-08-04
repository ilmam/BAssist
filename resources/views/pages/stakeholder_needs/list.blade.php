@extends(ui_layout())

@section('main')
    @php
        use App\Helpers\ListUi;

        $preserve = ListUi::contextFilters($listFilters ?? []);
        $orphans = ListUi::orphansToggle(model_route($model, 'index'), $listFilters ?? []);

        $relationColumns = [
            ListUi::childLinkColumn('BusinessNeed', 'stakeholder_need_id', 'business_needs_count', [
                'title' => __('ui.business_needs'),
                'icon' => 'abstract-26',
                'preserve' => $preserve,
            ]),
            ListUi::childLinkColumn('Stakeholder', 'stakeholder_need_id', 'stakeholders_count', [
                'title' => __('ui.stakeholders'),
                'icon' => 'people',
                'preserve' => $preserve,
            ]),
        ];

        $toolbarExtras = '<a href="'.e($orphans['url']).'" class="kt-btn kt-btn-sm '.($orphans['active'] ? 'kt-btn-warning' : 'kt-btn-outline').'">'
            .e(__('ui.show_orphans'))
            .'</a>';
    @endphp

    @include('pages.partials.entity-list', [
        'listHelp' => __('ui.babok_doc_stakeholder_requirements_note'),
    ])
@endsection

@push('styles')
    @include('pages.partials.orphan-row-styles')
@endpush
