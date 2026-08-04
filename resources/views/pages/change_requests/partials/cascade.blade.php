<div class="mt-6">
    <h3 class="text-sm font-medium mb-2">{{ __('ui.change_request_cascade_title') }}</h3>
    <p class="text-sm text-muted-foreground mb-3">{{ __('ui.change_request_cascade_help') }}</p>

    @if (($cascade ?? []) === [])
        <p class="text-sm text-muted-foreground">{{ __('ui.change_request_cascade_empty') }}</p>
    @else
        <ul class="space-y-1 text-sm">
            @foreach ($cascade as $item)
                <li>
                    <span class="kt-badge kt-badge-sm kt-badge-outline me-1">
                        {{ $item['type'] }}
                    </span>
                    @if (! empty($item['code']))
                        <span class="text-muted-foreground me-1">{{ $item['code'] }}</span>
                    @endif
                    {{ $item['title'] }}
                </li>
            @endforeach
        </ul>
    @endif
</div>
