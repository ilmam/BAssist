@extends(ui_layout())

@section('main')
    @php
        use App\Helpers\ListUi;

        $preserve = ListUi::contextFilters($listFilters ?? []);

        $relationColumns = [
            ListUi::childLinkColumn('BusinessObjective', 'project_id', 'business_objectives_count', [
                'title' => __('ui.business_objectives'),
                'icon' => 'chart-line-up-2',
                'preserve' => $preserve,
            ]),
            ListUi::childLinkColumn('BusinessNeed', 'project_id', 'business_needs_count', [
                'title' => __('ui.business_needs'),
                'icon' => 'abstract-26',
                'preserve' => $preserve,
            ]),
            ListUi::childLinkColumn('Stakeholder', 'project_id', 'stakeholders_count', [
                'title' => __('ui.stakeholders'),
                'icon' => 'people',
                'preserve' => $preserve,
            ]),
            ListUi::childLinkColumn('StakeholderNeed', 'project_id', 'stakeholder_needs_count', [
                'title' => __('ui.stakeholder_needs'),
                'icon' => 'questionnaire-tablet',
                'preserve' => $preserve,
            ]),
        ];
    @endphp

    @include('pages.partials.entity-list')
@endsection

@push('styles')
    @include('pages.partials.orphan-row-styles')
@endpush
