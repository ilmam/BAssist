@php
    $modelName = class_basename($model);
    $resource = \Illuminate\Support\Str::plural(\Illuminate\Support\Str::snake($modelName));
    $storeUrl = model_route($model, 'store');
    $updateUrlTemplate = url($resource.'/~id~');
    $destroyUrlTemplate = url($resource.'/~id~');
    $labelField = $labelField ?? 'title';
    $canUpdate = entity_can($model, 'update');
    $canDelete = entity_can($model, 'delete');
    $modalTitle = __('ui.quick_create').' · '.$modelName;
@endphp

<div
    data-quick-create
    data-store-url="{{ $storeUrl }}"
    data-update-url-template="{{ $updateUrlTemplate }}"
    data-destroy-url-template="{{ $destroyUrlTemplate }}"
    data-label-field="{{ $labelField }}"
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

            <div class="border-t border-border pt-4" data-qc-session>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <h4 class="text-sm font-medium text-foreground m-0">
                        {{ __('ui.added_this_session') }}
                        <span class="text-muted-foreground" data-qc-count>(0)</span>
                    </h4>
                </div>

                <p class="text-sm text-secondary-foreground m-0" data-qc-empty>{{ __('ui.no_session_records') }}</p>
                <ul class="list-none m-0 p-0 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2" data-qc-list hidden></ul>
            </div>
        </div>
    </x-modal-content>
</div>
