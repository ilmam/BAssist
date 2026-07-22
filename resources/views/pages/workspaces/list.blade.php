@extends(ui_layout())

@section('main')
    @php
        use App\Helpers\ListUi;

        $preserve = ListUi::contextFilters($listFilters ?? []);

        $relationColumns = [
            ListUi::childLinkColumn('Project', 'workspace_id', 'projects_count', [
                'title' => __('ui.projects'),
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
