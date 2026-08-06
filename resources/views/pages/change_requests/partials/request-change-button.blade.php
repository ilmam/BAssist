@php
    $projectId = (int) ($dto->project_id ?? 0);
    $stakeholderNeedId = (int) ($stakeholderNeedId ?? $dto->id ?? 0);

    $query = ['project_id' => $projectId];
    if ($stakeholderNeedId > 0) {
        $query['stakeholder_need_id'] = $stakeholderNeedId;
    }

    $requestChangeUrl = model_modal_path('ChangeRequest', 'create').'?'.http_build_query($query);
@endphp

@if (entity_can('ChangeRequest', 'create') && $projectId > 0 && $stakeholderNeedId > 0)
    <x-button
        type="link"
        href="{{ $requestChangeUrl }}"
        color="primary"
        class="js-open-modal"
        data-modal-url="{{ $requestChangeUrl }}"
    >{{ __('ui.request_change') }}</x-button>
@endif
