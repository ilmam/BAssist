@extends(ui_layout())

@section('main')
    @php
        use App\Helpers\ListUi;

        $preserve = ListUi::contextFilters($listFilters ?? []);
        $orphans = ListUi::orphansToggle(model_route($model, 'index'), $listFilters ?? []);

        $relationColumns = [
            ListUi::childLinkColumn('BusinessObjective', 'business_need_id', 'business_objectives_count', [
                'title' => __('ui.business_objectives'),
                'icon' => 'chart-line-up-2',
                'preserve' => $preserve,
            ]),
            ListUi::childLinkColumn('StakeholderNeed', 'business_need_id', 'stakeholder_needs_count', [
                'title' => __('ui.stakeholder_needs'),
                'icon' => 'questionnaire-tablet',
                'preserve' => $preserve,
            ]),
        ];

        $toolbarExtras = '<a href="'.e($orphans['url']).'" class="kt-btn kt-btn-sm '.($orphans['active'] ? 'kt-btn-warning' : 'kt-btn-outline').'">'
            .e(__('ui.show_orphans'))
            .'</a>';
    @endphp

    @include('pages.partials.entity-list')
@endsection

@push('styles')
    @include('pages.partials.orphan-row-styles')
@endpush
