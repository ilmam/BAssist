@extends(ui_layout())

@section('main')
    @include('pages.partials.entity-list', [
        'listHelp' => __('ui.babok_doc_governance_note'),
    ])
@endsection
