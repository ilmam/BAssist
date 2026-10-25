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
    @if (\App\Livewire\EntityForm::supports($formFields, $modelName))
        <livewire:entity-form
            :model="$modelName"
            :values="$dto->toArray()"
            :record-id="$dto->id ?? null"
            :in-modal="true"
            :key="'entity-form-modal-'.$modelName.'-'.($dto->id ?? 'new')"
        />
    @else
        <x-form
            id="modalForm"
            route="{{ $route }}"
            verb="{{ $verb }}"
            model="{{ $modelName }}"
            :dto="$dto"
            :fieldsArray="$formFields"
            :inModal="true"
        />
    @endif
</x-modal-content>
