@extends(ui_layout())

@section('main')
    @php
        /** @var \App\Models\ChangeRequest $changeRequest */
        $title = ($changeRequest->code ? $changeRequest->code.' — ' : '').$changeRequest->title;
    @endphp

    <x-card :title="__('ui.change_request_approve_taint')">
        <p class="text-sm text-muted-foreground mb-2">{{ $title }}</p>
        <p class="text-sm text-muted-foreground mb-4">{{ __('ui.change_request_taint_help') }}</p>

        <form id="approveTaintPageForm" method="POST" action="{{ $submitUrl }}">
            @csrf
            @if (($candidates ?? []) === [])
                <p class="text-sm mb-4">{{ __('ui.change_request_taint_empty') }}</p>
            @else
                <div class="space-y-2 mb-4 border border-border rounded-md p-3">
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
        </form>

        <x-slot:footer>
            <x-button type="link" href="{{ model_route('ChangeRequest', 'show', $changeRequest->id) }}" color="light">Cancel</x-button>
            <x-button type="submit" color="primary" form="approveTaintPageForm">{{ __('ui.change_request_confirm_approve') }}</x-button>
        </x-slot:footer>
    </x-card>
@endsection
