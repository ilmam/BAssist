{{--
    "Open issues" appendix + top-of-document notice (#8). Lists the threads printed
    above. Pass 'all' => true from a document that covers the whole project to
    also list open threads on records it does not print, so none is left out.
--}}
@php
    $printCollector = app(\App\Support\PrintComments::class);
    if (! empty($all)) {
        $printCollector->collectRemaining();
    }
    $printed = $printCollector->collected();
@endphp
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

@php $signoffs = app(\App\Support\PrintComments::class)->signoffs(); @endphp
@if ($signoffs !== [])
    @php
        $approvedCount = collect($signoffs)->filter(fn ($r) => $r['approval']?->isApproved())->count();
    @endphp
    <section class="print-appendix">
        <header class="section-banner section-banner--break">
            <h2 class="section-title" style="margin-bottom: 0;">{{ __('ui.review_signoff_title') }}</h2>
        </header>
        <p class="muted">{{ __('ui.review_signoff_intro', ['approved' => $approvedCount, 'total' => count($signoffs)]) }}</p>
        <table class="print-appendix__table">
            <thead>
                <tr>
                    <th>{{ __('ui.comments_appendix_item') }}</th>
                    <th>{{ __('ui.review_signoff_decision') }}</th>
                    <th>{{ __('ui.review_signoff_by') }}</th>
                    <th>{{ __('ui.review_signoff_date') }}</th>
                    <th>{{ __('ui.review_signoff_note') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($signoffs as $row)
                    @php $sig = $row['approval']; $item = $row['item']; @endphp
                    <tr>
                        <td>
                            @if ($item->getAttribute('code'))<span class="artifact__code">{{ $item->getAttribute('code') }}</span>@endif
                            {{ $item->getAttribute('title') }}
                        </td>
                        <td>{{ $sig ? ($sig->isApproved() ? __('ui.review_state_approved') : __('ui.review_state_changes')) : __('ui.review_state_none') }}</td>
                        <td>{{ $sig?->user?->name ?? '—' }}</td>
                        <td>{{ $sig?->created_at?->format('Y-m-d') ?? '—' }}</td>
                        <td>{{ $sig?->note ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endif
