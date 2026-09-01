@php
    $modelName = class_basename($model);
    $recordId = (int) ($recordId ?? ($dto->id ?? 0));
    $attachments = $attachments ?? [];
    if ($attachments === [] && entity_attachable($modelName) && $recordId > 0) {
        $attachments = app(\App\Services\AttachmentService::class)->list($modelName, $recordId);
    }
    $canUpdate = entity_can($modelName, 'update');
    $canView = entity_can($modelName, 'view');
    $showPanel = entity_attachable($modelName) && $recordId > 0 && ($canView || $canUpdate);
    $accept = implode(',', array_map(fn ($ext) => '.'.$ext, config('attachments.extensions', [])));
    $maxKb = (int) config('attachments.max_kilobytes', 10240);
    $maxLabel = ($maxKb >= 1024 && $maxKb % 1024 === 0) ? ($maxKb / 1024).' MB' : $maxKb.' KB';
@endphp

@if ($showPanel)
    <section class="entity-attachments rounded-lg border border-border p-4 space-y-3" data-entity-attachments>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <h3 class="text-base font-semibold text-foreground">{{ __('ui.attachments') }}</h3>
            @if ($canUpdate)
                <form
                    method="POST"
                    action="{{ route(model_route_name($modelName, 'attachments.store'), $recordId) }}"
                    enctype="multipart/form-data"
                    class="entity-attachments__upload"
                >
                    @csrf
                    <input
                        type="file"
                        name="file"
                        required
                        accept="{{ $accept }}"
                        class="entity-attachments__file"
                        aria-label="{{ __('ui.attachments_choose') }}"
                    >
                    <x-button type="submit" color="primary" size="sm">{{ __('ui.attachments_upload') }}</x-button>
                </form>
            @endif
        </div>
        @error('file')
            <p class="text-sm text-danger">{{ $message }}</p>
        @enderror
        <p class="text-xs text-muted-foreground">{{ __('ui.attachments_hint', ['max' => $maxLabel]) }}</p>
        @if ($attachments !== [])
            <ul class="divide-y divide-border rounded-lg border border-border">
                @foreach ($attachments as $attachment)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5">
                        <div class="min-w-0">
                            <a
                                href="{{ route(model_route_name($modelName, 'attachments.show'), [$recordId, $attachment->id]) }}"
                                class="text-sm text-foreground hover:text-primary hover:underline"
                            >{{ $attachment->original_name }}</a>
                            <span class="ms-2 text-xs text-muted-foreground">{{ $attachment->sizeLabel() }}</span>
                        </div>
                        @if ($canUpdate)
                            <form
                                method="POST"
                                action="{{ route(model_route_name($modelName, 'attachments.destroy'), [$recordId, $attachment->id]) }}"
                                onsubmit="return confirm(@json(__('ui.attachments_confirm_delete')))"
                            >
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" color="outline" size="sm">{{ __('ui.attachments_remove') }}</x-button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-muted-foreground">{{ __('ui.attachments_empty') }}</p>
        @endif
    </section>
@endif
