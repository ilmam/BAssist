{{-- "Open issues" appendix + top-of-document notice (#8). Lists only threads printed above. --}}
@php $printed = app(\App\Support\PrintComments::class)->collected(); @endphp
@if ($printed !== [])
    @push('print-notice')
        <p class="print-notice">
            {{ trans_choice('ui.comments_print_notice', count($printed), ['count' => count($printed)]) }}
        </p>
    @endpush

    <section class="print-appendix">
        <header class="section-banner section-banner--break">
            <h2 class="section-title" style="margin-bottom: 0;">{{ __('ui.comments_appendix_title') }}</h2>
        </header>
        <p class="muted">{{ __('ui.comments_appendix_intro') }}</p>
        <table class="print-appendix__table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('ui.comments_appendix_item') }}</th>
                    <th>{{ __('ui.comments_appendix_comment') }}</th>
                    <th>{{ __('ui.comments_appendix_by') }}</th>
                    <th>{{ __('ui.comments_appendix_age') }}</th>
                    <th>{{ __('ui.comments_appendix_replies') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($printed as $index => $row)
                    @php
                        $item = $row['item'];
                        $thread = $row['thread'];
                        $days = (int) $thread->created_at?->diffInDays(now());
                    @endphp
                    <tr>
                        <td>C{{ $row['number'] }}</td>
                        <td>
                            @if ($item->getAttribute('code'))<span class="artifact__code">{{ $item->getAttribute('code') }}</span>@endif
                            {{ $item->getAttribute('title') ?? $item->getAttribute('name') }}
                        </td>
                        <td>{!! \App\Services\CommentService::render(\Illuminate\Support\Str::limit($thread->body, 280)) !!}</td>
                        <td>{{ $thread->author?->name ?? '—' }}</td>
                        <td>{{ trans_choice('ui.comments_age_days', $days, ['count' => $days]) }}</td>
                        <td>{{ $thread->replies->count() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endif
