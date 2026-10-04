{{-- Record history (#8): who changed what, when. Collapsed by default. --}}
@php
    use Illuminate\Support\Str;

    $statusNames = \App\Models\Status::query()->pluck('name', 'id');
    $priorityNames = \App\Models\Priority::query()->pluck('name', 'id');
    $label = fn (string $field) => Str::headline(preg_replace('/_id$/', '', $field));
    $value = function (string $field, $v) use ($statusNames, $priorityNames) {
        if ($v === null || $v === '') {
            return '—';
        }
        return match ($field) {
            'status_id' => $statusNames[$v] ?? '#'.$v,
            'priority_id' => $priorityNames[$v] ?? '#'.$v,
            default => is_bool($v) ? ($v ? 'Yes' : 'No') : (str_ends_with($field, '_id') ? '#'.$v : Str::limit((string) $v, 120)),
        };
    };
@endphp

<details class="ba-history">
    <summary>
        <span class="ba-history__title">{{ __('ui.history_title') }}</span>
        <span class="ba-history__count">{{ trans_choice('ui.history_count', $history->count(), ['count' => $history->count()]) }}</span>
    </summary>
    @if ($history->isEmpty())
        <p class="ba-comments__empty">{{ __('ui.history_empty') }}</p>
    @else
        <ol class="ba-history__list">
            @foreach ($history as $entry)
                <li class="ba-history__item ba-history__item--{{ $entry->event }}">
                    <span class="ba-history__dot" aria-hidden="true"></span>
                    <div class="min-w-0">
                        <div class="ba-history__line">
                            <strong>{{ $entry->user?->name ?? __('ui.history_system') }}</strong>
                            {{ __('ui.history_event_'.$entry->event) }}
                            <time datetime="{{ $entry->created_at?->toIso8601String() }}">{{ $entry->created_at?->format('Y-m-d H:i') }}</time>
                        </div>
                        @if ($entry->event === 'updated' && is_array($entry->changes))
                            <ul class="ba-history__changes">
                                @foreach ($entry->changes as $field => [$old, $new])
                                    <li><span class="ba-history__field">{{ $label($field) }}</span> <del>{{ $value($field, $old) }}</del> → <ins>{{ $value($field, $new) }}</ins></li>
                                @endforeach
                            </ul>
                        @elseif ($entry->event === 'approval_reset' && ! empty($entry->changes['fields']))
                            <p class="ba-history__note">{{ __('ui.history_reset_fields', ['fields' => collect($entry->changes['fields'])->map($label)->implode(', ')]) }}</p>
                        @endif
                        @if ($entry->note)
                            <p class="ba-history__note">“{{ $entry->note }}”</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</details>
