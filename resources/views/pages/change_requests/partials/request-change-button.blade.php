@php
    $projectId = (int) ($dto->project_id ?? 0);
    $stakeholderNeedId = (int) ($stakeholderNeedId ?? $dto->stakeholder_need_id ?? 0);

    // Feature/FR parented under a CR: use that CR's SN when available.
    if ($stakeholderNeedId < 1 && isset($dto->change_request_id) && (int) $dto->change_request_id > 0) {
        $stakeholderNeedId = (int) (\App\Models\ChangeRequest::query()
            ->whereKey((int) $dto->change_request_id)
            ->value('stakeholder_need_id') ?? 0);
    }

    $query = ['project_id' => $projectId];
    if ($stakeholderNeedId > 0) {
        $query['stakeholder_need_id'] = $stakeholderNeedId;
    }

    $requestChangeUrl = model_modal_path('ChangeRequest', 'create').'?'.http_build_query($query);
@endphp

@if (entity_can('ChangeRequest', 'create') && $projectId > 0)
    <x-button
        type="link"
        href="{{ $requestChangeUrl }}"
        color="primary"
        class="js-open-modal"
        data-modal-url="{{ $requestChangeUrl }}"
    >{{ __('ui.request_change') }}</x-button>
@endif
