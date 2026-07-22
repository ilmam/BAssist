@extends(ui_layout())

@section('main')
    @php
        use App\Helpers\ListUi;

        $preserve = ListUi::contextFilters($listFilters ?? []);

        $relationColumns = [
            ListUi::childLinkColumn('BusinessNeed', 'business_objective_id', 'business_needs_count', [
                'title' => __('ui.business_needs'),
                'icon' => 'abstract-26',
                'preserve' => $preserve,
            ]),
        ];
    @endphp

    @include('pages.partials.entity-list')
@endsection

@push('styles')
    @include('pages.partials.orphan-row-styles')
@endpush
