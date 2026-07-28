@php
    $createRoute = model_route_name($model, 'create');
    $createPageUrl = model_route($model, 'create');
    $createModalUrl = model_modal_path($model, 'create');
    $quickCreateModalUrl = model_modal_path($model, 'quick-create');
    $modelAllowsModals = config('crud.models.'.class_basename($model).'.use_modals', true) !== false;
    $useModalCreate = config('ui.modal_create', true) && $modelAllowsModals;
    $useQuickCreate = config('ui.modal_quick_create', true) && $modelAllowsModals;
    $theme = ui_theme();
@endphp

@if (entity_can($model, 'create'))
    @if ($useModalCreate)
        @if ($theme === 'metronic9')
            <div class="create-split-btn inline-flex items-stretch">
                <x-button
                    type="link"
                    href="{{ $createRoute }}"
                    icon="plus"
                    iconOnly="true"
                    color="primary"
                    activeColor="primary"
                    class="js-open-modal create-split-btn__main"
                    data-modal-url="{{ $createModalUrl }}"
                ></x-button>
                <div class="inline-flex" data-kt-dropdown="true" data-kt-dropdown-trigger="click">
                    <button type="button" class="kt-btn kt-btn-icon kt-btn-primary create-split-btn__toggle" data-kt-dropdown-toggle="true" aria-label="More create options">
                        <i class="ki-filled ki-down text-xs"></i>
                    </button>
                    <div class="kt-dropdown-menu min-w-[180px]" data-kt-dropdown-menu="true">
                        <a href="{{ $createModalUrl }}" class="kt-dropdown-menu-link js-open-modal" data-modal-url="{{ $createModalUrl }}" data-kt-dropdown-dismiss="true">Open</a>
                        @if ($useQuickCreate)
                            <a
                                href="{{ $quickCreateModalUrl }}"
                                class="kt-dropdown-menu-link js-open-modal"
                                data-modal-url="{{ $quickCreateModalUrl }}"
                                data-modal-size="full"
                                data-kt-dropdown-dismiss="true"
                            >{{ __('ui.quick_create') }}</a>
                        @endif
                        <a href="{{ $createPageUrl }}" class="kt-dropdown-menu-link" target="_blank" rel="noopener" data-kt-dropdown-dismiss="true">Open in new page</a>
                    </div>
                </div>
            </div>
        @else
            <div class="btn-group">
                <x-button
                    type="link"
                    href="{{ $createRoute }}"
                    icon="plus"
                    iconOnly="true"
                    color="primary"
                    activeColor="primary"
                    class="js-open-modal"
                    data-modal-url="{{ $createModalUrl }}"
                ></x-button>
                <button type="button" class="btn btn-md btn-icon btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"></button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item js-open-modal" data-modal-url="{{ $createModalUrl }}" href="{{ $createModalUrl }}">Open</a></li>
                    @if ($useQuickCreate)
                        <li>
                            <a
                                class="dropdown-item js-open-modal"
                                data-modal-url="{{ $quickCreateModalUrl }}"
                                href="{{ $quickCreateModalUrl }}"
                            >{{ __('ui.quick_create') }}</a>
                        </li>
                    @endif
                    <li><a class="dropdown-item" href="{{ $createPageUrl }}" target="_blank" rel="noopener">Open in new page</a></li>
                </ul>
            </div>
        @endif
    @else
        <x-button type="link" href="{{ $createRoute }}" icon="plus" iconOnly="true" color="primary" activeColor="primary"></x-button>
    @endif
@endif
