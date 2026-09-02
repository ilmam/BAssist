@php
    $modelName = class_basename($model);
    $recordId = (int) ($recordId ?? ($dto->id ?? 0));
    $fieldName = $fieldName ?? 'attachments';
    $mode = $mode ?? 'details';
    $readonly = (bool) ($readonly ?? false);
    $attachments = $attachments ?? [];
    if ($attachments === [] && entity_attachable($modelName) && $recordId > 0) {
        $attachments = app(\App\Services\AttachmentService::class)->list($modelName, $recordId);
    }
    $canUpdate = ! $readonly && (
        $recordId > 0
            ? entity_can($modelName, 'update')
            : entity_can($modelName, 'create')
    );
    $canView = entity_can($modelName, 'view');
    $showPanel = entity_attachable($modelName) && ($canView || $canUpdate || $mode === 'field');
    $accept = implode(',', array_map(fn ($ext) => '.'.$ext, config('attachments.extensions', [])));
    $maxKb = (int) config('attachments.max_kilobytes', 10240);
    $maxLabel = ($maxKb >= 1024 && $maxKb % 1024 === 0) ? ($maxKb / 1024).' MB' : $maxKb.' KB';
    $isField = $mode === 'field';
@endphp

@if ($showPanel)
    <div class="entity-attachments space-y-2" data-entity-attachments data-attachments-field="{{ $fieldName }}">
        @if (! $isField)
            <h3 class="text-base font-semibold text-foreground">{{ __('ui.attachments') }}</h3>
        @endif
        @if ($isField && $canUpdate)
            <input
                type="file"
                name="{{ $fieldName }}[]"
                id="{{ $fieldName }}"
                multiple
                accept="{{ $accept }}"
                class="{{ $fileInputClass ?? 'kt-input file:me-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary' }}"
                aria-label="{{ __('ui.attachments_choose') }}"
            >
        @endif

        <p class="text-xs text-muted-foreground">{{ __('ui.attachments_hint', ['max' => $maxLabel]) }}</p>

        @error($fieldName)
            <p class="text-sm text-danger">{{ $message }}</p>
        @enderror
        @error($fieldName.'.*')
            <p class="text-sm text-danger">{{ $message }}</p>
        @enderror

        @if ($attachments !== [])
            <ul class="divide-y divide-border rounded-lg border border-border">
                @foreach ($attachments as $attachment)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5">
                        <div class="min-w-0">
                            @if ($canView && $recordId > 0)
                                <a
                                    href="{{ route(model_route_name($modelName, 'attachments.show'), [$recordId, $attachment->id]) }}"
                                    class="text-sm text-foreground hover:text-primary hover:underline"
                                >{{ $attachment->original_name }}</a>
                            @else
                                <span class="text-sm text-foreground">{{ $attachment->original_name }}</span>
                            @endif
                            <span class="ms-2 text-xs text-muted-foreground">{{ $attachment->sizeLabel() }}</span>
                        </div>
                        @if ($isField && $canUpdate)
                            <label class="flex items-center gap-2 text-sm text-muted-foreground">
                                <input
                                    type="checkbox"
                                    name="remove_{{ $fieldName }}[]"
                                    value="{{ $attachment->id }}"
                                    class="kt-checkbox"
                                >
                                {{ __('ui.attachments_remove') }}
                            </label>
                        @endif
                    </li>
                @endforeach
            </ul>
        @elseif (! $isField)
            <p class="text-sm text-muted-foreground">{{ __('ui.attachments_empty') }}</p>
        @endif
    </div>
@endif
