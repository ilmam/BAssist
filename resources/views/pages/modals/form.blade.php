@php
    $modelName = class_basename($model);
    $verb = in_array($operation, ['insert', 'create'], true) ? 'POST' : 'PUT';
    $action = in_array($operation, ['insert', 'create'], true) ? 'store' : 'update';
    $title = ucfirst($operation).' '.$modelName;
    $route = model_route_name($model, $action);
@endphp

<x-modal-content :title="$title">
    <x-slot:actions>
        <x-open-as-page :href="record_form_page_url($modelName, $dto ?? null)" />
    </x-slot:actions>
    <x-form
        id="modalForm"
        route="{{ $route }}"
        verb="{{ $verb }}"
        model="{{ $modelName }}"
        :dto="$dto"
        :fieldsArray="$formFields"
        :inModal="true"
    />
</x-modal-content>
