<?php

namespace App\View\Components;

use App\Support\EntityAccess;
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
        // DataTables autoWidth (default off — avoids equal-width <colgroup> sizing).
        'autoWidth' => false,
    ];

    private $buttons = [];

    public function __construct(
        public array $options,
        public string $id = 'datatable',
        public string $class = 'table',
        public bool $defaultButtons = false,
        public ?bool $collapsedActions = null,
    ) {
        $this->options = array_merge($this->defaultOptions, $options);

        // Keep component id and options['id'] aligned so multiple tables on one page work.
        if (($this->options['id'] ?? null) !== null && $this->options['id'] !== '') {
            $this->id = (string) $this->options['id'];
        } elseif ($this->id !== '' && $this->id !== 'datatable') {
            $this->options['id'] = $this->id;
        } else {
            $this->options['id'] = $this->options['id'] ?? 'datatable';
            $this->id = (string) $this->options['id'];
        }

        if ($this->collapsedActions === null) {
            if (array_key_exists('collapsedActions', $this->options)) {
                $this->collapsedActions = (bool) $this->options['collapsedActions'];
            } else {
                $this->collapsedActions = (bool) config('ui.datatables.collapsed_actions', false);
            }
        }

        $this->prepareDatatable();
    }

    public function render()
    {
        return $this->themeView('datatable');
    }

    public function prepareDatatable()
    {
        $model = $this->options['model'] ?: ($this->options['modelName'] ?? '');
        $resource = Str::plural(Str::snake($model));
        $baseUrl = url($resource);
        $useModals = config("crud.models.{$model}.use_modals", true) !== false;

        $modalViewUrl = $baseUrl.'/modal/{id}/view';
        $modalEditUrl = $baseUrl.'/modal/{id}/edit';
        $modalDeleteUrl = $baseUrl.'/modal/{id}/delete';

        // Code column opens the same view modal as the eye action (all lists).
        $this->options['codePageUrl'] = $baseUrl.'/{id}';
        $this->options['codeModalUrl'] = ($useModals && config('ui.modal_view', true))
            ? $modalViewUrl
            : null;

        $this->buttons = [
            [
                'action' => 'show',
                'text' => '',
                'icon' => 'eye',
                'link' => $baseUrl.'/{id}',
                'modalUrl' => ($useModals && config('ui.modal_view', true)) ? $modalViewUrl : null,
            ],
            [
                'action' => 'edit',
                'text' => '',
                'icon' => 'pencil',
                'link' => $baseUrl.'/{id}/edit',
                'modalUrl' => ($useModals && config('ui.modal_edit', true)) ? $modalEditUrl : null,
                // Split "Open / Open in new page" only when actions are shown inline.
                'menu' => ! $this->collapsedActions && $useModals && config('ui.modal_edit', true),
            ],
            [
                'action' => 'delete',
                'text' => '',
                'icon' => 'trash',
                'link' => $baseUrl.'/{id}',
                'modalUrl' => $modalDeleteUrl,
            ],
        ];

        $this->options['columns'] = array_diff($this->options['columns'], $this->options['exclude']);

        if ($this->defaultButtons) {
            $buttons = array_values(array_filter(
                $this->buttons,
                fn (array $button) => EntityAccess::can(
                    auth()->user(),
                    $model,
                    EntityAccess::abilityForTableAction($button['action'] ?? 'show')
                )
            ));

            $extraButtons = is_array($this->options['extraButtons'] ?? null)
                ? $this->options['extraButtons']
                : [];

            foreach ($extraButtons as $extraButton) {
                if (! is_array($extraButton)) {
                    continue;
                }

                if (! EntityAccess::can(
                    auth()->user(),
                    $model,
                    EntityAccess::abilityForTableAction($extraButton['action'] ?? 'show')
                )) {
                    continue;
                }

                $buttons[] = $extraButton;
            }

            if ($buttons !== []) {
                $this->options['columns'][] = [
                    'custom' => true,
                    'name' => 'actions',
                    'title' => '',
                    'style' => \App\Helpers\DatatableUi::actionsStyle($buttons, $this->collapsedActions),
                    'buttons' => $buttons,
                    'collapsed' => $this->collapsedActions,
                ];
            }
        }
    }
}
