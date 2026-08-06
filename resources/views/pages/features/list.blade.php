@extends(ui_layout())

@section('main')
    @php
        use App\Helpers\DatatableUi;
        use App\Helpers\ListUi;

        $preserve = ListUi::contextFilters($listFilters ?? []);
        $theme = function_exists('ui_theme') ? ui_theme() : 'metronic8';
        $showHref = e(url('features/{id}'));

        if ($theme === 'metronic9') {
            $scenariosTemplate = '<a href="'.$showHref.'" class="kt-btn kt-btn-sm kt-btn-ghost gap-1" title="'.e(__('ui.scenarios')).'">'
                .'<i class="ki-filled ki-'.e(entity_icon('Scenario')).'"></i>'
                .'<span class="text-xs font-medium">{scenarios_count}</span>'
                .'</a>';
        } else {
            $scenariosTemplate = '<a href="'.$showHref.'" class="btn btn-sm btn-light btn-active-light-primary" title="'.e(__('ui.scenarios')).'">'
                .'<i class="fa fa-list"></i> '
                .'<span>{scenarios_count}</span>'
                .'</a>';
        }

        $relationColumns = [
            [
                'custom' => true,
                'name' => 'scenarios_count',
                'title' => __('ui.scenarios'),
                'style' => DatatableUi::compactStyle(),
                'template' => $scenariosTemplate,
                'fields' => ['id', 'scenarios_count'],
            ],
        ];
    @endphp

    @include('pages.partials.entity-list', [
        'dto' => $dto,
        'model' => $model,
        'columns' => $columns,
        'relationColumns' => $relationColumns,
        'listFilters' => $listFilters ?? [],
        'allowedListFilters' => $allowedListFilters ?? [],
        'listHelp' => __('ui.babok_doc_acceptance_criteria_note'),
    ])
@endsection
