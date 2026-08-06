@extends(ui_layout())

@section('main')
    @php
        use App\Helpers\ListUi;

        $preserve = ListUi::contextFilters($listFilters ?? []);

        $relationColumns = [
            ListUi::childLinkColumn('BusinessObjective', 'project_id', 'business_objectives_count', [
                'title' => __('ui.business_objectives'),
                'preserve' => $preserve,
            ]),
            ListUi::childLinkColumn('BusinessNeed', 'project_id', 'business_needs_count', [
                'title' => __('ui.business_needs'),
                'preserve' => $preserve,
            ]),
            ListUi::childLinkColumn('Stakeholder', 'project_id', 'stakeholders_count', [
                'title' => __('ui.stakeholders'),
                'preserve' => $preserve,
            ]),
            ListUi::childLinkColumn('StakeholderNeed', 'project_id', 'stakeholder_needs_count', [
                'title' => __('ui.stakeholder_needs'),
                'preserve' => $preserve,
            ]),
            ListUi::childLinkColumn('Feature', 'project_id', 'features_count', [
                'title' => __('ui.features'),
                'preserve' => $preserve,
            ]),
            ListUi::childLinkColumn('StateFlow', 'project_id', 'state_flows_count', [
                'title' => __('ui.state_flows'),
                'preserve' => $preserve,
            ]),
            ListUi::childLinkColumn('SwimlaneFlow', 'project_id', 'swimlane_flows_count', [
                'title' => __('ui.swimlane_flows'),
                'preserve' => $preserve,
            ]),
        ];

        $downloadMenuItems = [];
        foreach (config('babok_documents.documents', []) as $docKey => $docMeta) {
            $downloadMenuItems[] = [
                'label' => __($docMeta['title']),
                'link' => url('projects/{id}/babok/'.$docKey),
                'target' => '_blank',
            ];
        }
        $downloadMenuItems[] = [
            'label' => __('ui.babok_documents'),
            'link' => url('projects/{id}/babok'),
        ];
        $downloadMenuItems[] = [
            'label' => __('ui.export_pack'),
            'link' => url('projects/{id}/export'),
            'target' => '_blank',
        ];

        $datatableOptions = [
            'extraButtons' => [
                [
                    'action' => 'show',
                    'text' => '',
                    'icon' => entity_icon('export_pack'),
                    'link' => url('projects/{id}/export'),
                    'target' => '_blank',
                    'title' => __('ui.project_downloads'),
                    'showText' => false,
                    'menu' => true,
                    'menuItems' => $downloadMenuItems,
                ],
            ],
        ];
    @endphp

    @include('pages.partials.entity-list')
@endsection

@push('styles')
    @include('pages.partials.orphan-row-styles')
@endpush
