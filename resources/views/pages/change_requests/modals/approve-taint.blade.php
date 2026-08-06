@php
    /** @var \App\Models\ChangeRequest $changeRequest */
    $title = ($changeRequest->code ? $changeRequest->code.' — ' : '').$changeRequest->title;
@endphp

<x-modal-content :title="__('ui.change_request_approve_taint')" size="lg">
    <form id="approveTaintForm" method="POST" action="{{ $submitUrl }}" data-modal-form="true">
        @csrf
        <p class="text-sm text-muted-foreground mb-4">{{ __('ui.change_request_taint_help') }}</p>

        @if (($candidates ?? []) === [])
            <p class="text-sm mb-4">{{ __('ui.change_request_taint_empty') }}</p>
        @else
            <div class="space-y-2 mb-4 max-h-80 overflow-y-auto border border-border rounded-md p-3">
                @foreach ($candidates as $item)
                    @php
                        $token = $item['type'].':'.$item['id'];
                        $label = trim(($item['code'] ? $item['code'].' — ' : '').$item['title']);
                        $typeLabel = $item['type'] === 'feature'
                            ? __('ui.feature')
                            : __('ui.functional_requirement');
                    @endphp
                    <label class="flex items-start gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="taint_items[]"
                            value="{{ $token }}"
                            class="mt-0.5"
                            @checked($item['selected'] ?? true)
                        >
                        <span>
                            <span class="text-muted-foreground">{{ $typeLabel }}</span>
                            — {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
        @endif

        <div class="flex justify-end gap-2.5">
            <x-modal-dismiss text="Cancel" />
            <button type="submit" class="{{ ui_btn_classes('primary') }}">{{ __('ui.change_request_confirm_approve') }}</button>
        </div>
    </form>
</x-modal-content>
