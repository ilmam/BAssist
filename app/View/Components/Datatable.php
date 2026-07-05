<?php

namespace App\View\Components;

use App\View\Concerns\ResolvesThemeView;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class Datatable extends Component
{
    use ResolvesThemeView;

    private $defaultOptions = [
        'exclude' => [],
        'dataRoute' => '',
        'dataRoutParameters' => [],
        'columns' => [],
        'model' => '',
    ];

    private $buttons = [];

    public function __construct(
        public array $options,
        public string $id = 'datatable',
        public string $class = 'table',
        public bool $defaultButtons = false
    ) {
        $this->options = array_merge($this->defaultOptions, $options);
        $this->prepareDatatable();
    }

    public function render()
    {
        return $this->themeView('datatable');
    }

    public function prepareDatatable()
    {
        $model = $this->options['model'] ?: ($this->options['modelName'] ?? 'Category');
        $resource = Str::plural(Str::snake($model));
        $baseUrl = url($resource);

        $modalViewUrl = $baseUrl.'/modal/{id}/view';
        $modalEditUrl = $baseUrl.'/modal/{id}/edit';
        $modalDeleteUrl = $baseUrl.'/modal/{id}/delete';

        $this->buttons = [
            [
                'action' => 'show',
                'text' => '',
                'icon' => 'eye',
                'link' => $baseUrl.'/{id}',
                'modalUrl' => config('ui.modal_view', true) ? $modalViewUrl : null,
            ],
            [
                'action' => 'edit',
                'text' => '',
                'icon' => 'pencil',
                'link' => $baseUrl.'/{id}/edit',
                'modalUrl' => config('ui.modal_edit', true) ? $modalEditUrl : null,
                'menu' => config('ui.modal_edit', true),
            ],
            [
                'action' => 'delete',
                'text' => '',
                'icon' => 'trash',
                'link' => '#',
                'modalUrl' => $modalDeleteUrl,
            ],
        ];

        $this->options['columns'] = array_diff($this->options['columns'], $this->options['exclude']);

        if ($this->defaultButtons) {
            $this->options['columns'][] = [
                'custom' => true,
                'name' => 'actions',
                'title' => '',
                'style' => 'width: 120px',
                'buttons' => $this->buttons,
            ];
        }
    }
}
