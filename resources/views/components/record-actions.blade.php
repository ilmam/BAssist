{{--
    Header actions of a record view: same buttons on the full page and in the pop-up.
    The pop-up additionally gets the "open as page" permalink.
    page-only: the entity has no pop-up form (its modal routes redirect to the page), so Edit navigates.
--}}
@props(['model', 'dto', 'inModal' => false, 'pageOnly' => false])
@php
    $modelName = class_basename($model);
    $editUrl = $pageOnly ? model_route($modelName, 'edit', $dto->id) : model_modal_path($modelName, 'edit', $dto->id);
    $deleteUrl = model_modal_path($modelName, 'delete', $dto->id);
@endphp
@if ($inModal)
    <x-open-as-page :href="model_route($modelName, 'show', $dto->id)" />
@endif
@if (entity_can($modelName, 'update'))
    @if ($pageOnly)
        <x-button type="link" href="{{ $editUrl }}" icon="pencil" iconOnly="true" color="primary" activeColor="primary" title="{{ __('ui.edit') }}" aria-label="{{ __('ui.edit') }}"></x-button>
    @else
        <x-button type="link" href="{{ $editUrl }}" icon="pencil" iconOnly="true" color="primary" activeColor="primary" class="js-open-modal" data-modal-url="{{ $editUrl }}" title="{{ __('ui.edit') }}" aria-label="{{ __('ui.edit') }}"></x-button>
    @endif
@endif
@if (entity_can($modelName, 'delete') && empty($dto->is_system))
    <x-button type="link" href="{{ $deleteUrl }}" icon="trash" iconOnly="true" color="danger" activeColor="warning" class="js-open-modal" data-modal-url="{{ $deleteUrl }}" title="{{ __('ui.delete') }}" aria-label="{{ __('ui.delete') }}"></x-button>
@endif
