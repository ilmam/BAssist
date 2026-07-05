@php
    $modelName = class_basename($model);
    $verb = 'PUT';
    $route = model_route_name($model, 'update');
    $title = ucfirst($operation).' '.$modelName;
@endphp

<div class="kt-modal-header">
    <h3 class="kt-modal-title">{{ $title }}</h3>
    <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true" type="button">
        <i class="ki-filled ki-cross"></i>
    </button>
</div>

<div class="kt-modal-body">
    <x-form
        id="modalForm"
        route="{{ $route }}"
        verb="{{ $verb }}"
        model="{{ $modelName }}"
        :dto="$dto"
        :fieldsArray="$formFields"
        :inModal="true"
    />
</div>
