@php
    $affectedType = $affectedType ?? null;
    $projectId = (int) ($dto->project_id ?? 0);
    $affectedId = (int) ($dto->id ?? 0);
    $requestChangeUrl = model_modal_path('ChangeRequest', 'create').'?'.http_build_query([
        'project_id' => $projectId,
        'affected_type' => $affectedType,
        'affected_id' => $affectedId,
    ]);
@endphp

@if (entity_can('ChangeRequest', 'create') && $affectedType && $projectId > 0 && $affectedId > 0)
    <x-button
        type="link"
        href="{{ $requestChangeUrl }}"
        color="primary"
        class="js-open-modal"
        data-modal-url="{{ $requestChangeUrl }}"
    >{{ __('ui.request_change') }}</x-button>
@endif
