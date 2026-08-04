@extends(ui_layout())

@section('main')
    @php
        use App\Helpers\ListUi;

        $preserve = ListUi::contextFilters($listFilters ?? []);

        $relationColumns = [
            ListUi::childLinkColumn('StakeholderNeed', 'stakeholder_id', 'stakeholder_needs_count', [
                'title' => __('ui.stakeholder_needs'),
                'icon' => 'questionnaire-tablet',
                'preserve' => $preserve,
            ]),
        ];
    @endphp

    @include('pages.partials.entity-list', [
        'listHelp' => __('ui.babok_doc_stakeholder_engagement_note'),
    ])
@endsection

@push('styles')
    @include('pages.partials.orphan-row-styles')
@endpush
