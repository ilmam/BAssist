@extends(ui_layout())

@section('main')
    @php
        $theme = function_exists('ui_theme') ? ui_theme() : 'metronic8';
        $narrow = 'width: 6.5rem';

        // Keep title breathing room; shrink code / project / status.
        $columns = collect($columns)->map(function ($col) use ($narrow) {
            $name = is_array($col) ? (string) ($col['data'] ?? $col['name'] ?? '') : (string) $col;
            $root = str_contains($name, '.') ? explode('.', $name, 2)[0] : $name;

            if (! in_array($root, ['code', 'project', 'status'], true)) {
                return $col;
            }

            return [
                'data' => $name,
                'name' => $name,
                'style' => $narrow,
            ];
        })->all();

        if ($theme === 'metronic9') {
            $scenariosTemplate = '<span class="inline-flex items-center gap-1 text-xs font-medium text-foreground" title="'.e(__('ui.scenarios')).'">'
                .'<i class="ki-filled ki-'.e(entity_icon('Scenario')).'"></i>'
                .'<span>{scenarios_count}</span>'
                .'</span>';
        } else {
            $scenariosTemplate = '<span title="'.e(__('ui.scenarios')).'">'
                .'<i class="fa fa-list"></i> '
                .'<span>{scenarios_count}</span>'
                .'</span>';
        }

        $relationColumns = [
            [
                'custom' => true,
                'name' => 'scenarios_count',
                'title' => __('ui.scenarios'),
                'style' => 'width: 5.5rem; white-space: nowrap',
                'template' => $scenariosTemplate,
                'fields' => ['scenarios_count'],
            ],
        ];

        // Features-only: View becomes a split button (like Edit) with View raw.
        $featureViewUrl = url('features/{id}');
        $featureViewModalUrl = url('features/modal/{id}/view');
        $featureRawModalUrl = url('features/modal/{id}/raw');
        $datatableOptions = [
            'actionOverrides' => [
                'show' => [
                    'menu' => true,
                    'menuItems' => [
                        [
                            'label' => __('ui.view'),
                            'link' => $featureViewUrl,
                            'modalUrl' => $featureViewModalUrl,
                        ],
                        [
                            'label' => __('ui.view_raw'),
                            'link' => $featureViewUrl,
                            'modalUrl' => $featureRawModalUrl,
                        ],
                    ],
                ],
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
        'datatableOptions' => $datatableOptions,
    ])
@endsection
