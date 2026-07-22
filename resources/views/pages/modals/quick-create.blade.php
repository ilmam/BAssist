@php
    $modelName = class_basename($model);
    $resource = \Illuminate\Support\Str::plural(\Illuminate\Support\Str::snake($modelName));
    $storeUrl = model_route($model, 'store');
    $updateUrlTemplate = url($resource.'/~id~');
    $destroyUrlTemplate = url($resource.'/~id~');
    $labelField = $labelField ?? 'title';
    $sessionColumns = $sessionColumns ?? ['id'];
    $formFields = $formFields ?? [];
    $sessionColumnMeta = array_map(
        static function (string $key) use ($formFields): array {
            $meta = [
                'key' => $key,
                'label' => \App\Helpers\Ui::fieldLabel($key),
            ];

            $field = $formFields[$key] ?? null;
            $type = is_array($field) ? ($field['type'] ?? null) : null;
            if (in_array($type, ['select', 'kt-select'], true) && ! empty($field['list'])) {
                $list = $field['list'];
                if ($list instanceof \Illuminate\Support\Collection) {
                    $list = $list->all();
                }
                $options = [];
                foreach ((array) $list as $id => $label) {
                    $options[(string) $id] = (string) $label;
                }
                $meta['options'] = $options;
            }

            return $meta;
        },
        $sessionColumns
    );
    $canUpdate = entity_can($model, 'update');
    $canDelete = entity_can($model, 'delete');
    $modalTitle = __('ui.quick_create').' · '.$modelName;
    $isMetronic9 = ui_theme() === 'metronic9';
    $sessionTableClass = $isMetronic9
        ? 'kt-table kt-table-border table-hover w-full text-sm'
        : 'table table-row-dashed table-hover align-middle gs-0 gy-2 fs-7 mb-0';
    $sessionTableWrapClass = $isMetronic9
        ? 'kt-table-wrapper overflow-x-auto'
        : 'table-responsive';
@endphp

<div
    data-quick-create
    data-store-url="{{ $storeUrl }}"
    data-update-url-template="{{ $updateUrlTemplate }}"
    data-destroy-url-template="{{ $destroyUrlTemplate }}"
    data-label-field="{{ $labelField }}"
    data-qc-columns='@json($sessionColumnMeta)'
    data-can-update="{{ $canUpdate ? '1' : '0' }}"
    data-can-delete="{{ $canDelete ? '1' : '0' }}"
    data-i18n-add="{{ __('ui.add_another') }}"
    data-i18n-update="{{ __('ui.update_record') }}"
    data-i18n-edit="{{ __('ui.edit') }}"
    data-i18n-delete="{{ __('ui.delete') }}"
    data-i18n-just-now="{{ __('ui.just_now') }}"
    data-i18n-confirm-delete="{{ __('ui.confirm_delete_session_record') }}"
>
    <x-modal-content :title="$modalTitle" size="full">
        <div class="flex flex-col gap-4">
            <x-form
                id="quickCreateForm"
                route="{{ model_route_name($model, 'store') }}"
                verb="POST"
                model="{{ $modelName }}"
                :dto="$dto"
                :fieldsArray="$formFields"
                :inModal="true"
                :quickCreate="true"
                :hiddenDefaults="$hiddenDefaults ?? []"
            />

            <div class="border-t border-border pt-4" data-qc-session hidden>
                <div class="flex items-center justify-between gap-2 mb-3" data-qc-session-header>
                    <h4 class="text-sm font-medium text-foreground m-0">
                        {{ __('ui.added_this_session') }}
                        <span class="text-muted-foreground" data-qc-count>(0)</span>
                    </h4>
                </div>

                <div class="{{ $sessionTableWrapClass }}">
                    <table class="{{ $sessionTableClass }}" data-qc-table>
                        <thead>
                            <tr data-qc-head></tr>
                        </thead>
                        <tbody data-qc-list></tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-modal-content>
</div>
