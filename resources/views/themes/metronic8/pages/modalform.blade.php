@php
    $modelName = class_basename($model);
    $verb = 'PUT';
    $route = model_route_name($model, 'update');
    $title = ucfirst($operation).' '.$modelName;
@endphp

<div class="modal-header">
    <h3 class="modal-title">{{ $title }}</h3>
    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close">
        <i class="fa fa-times"></i>
    </button>
</div>

<div class="modal-body">
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
