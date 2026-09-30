{{-- Word-style margin balloons for one item's open threads (#8). Floats into the comment margin. --}}
@php
    $printComments = app(\App\Support\PrintComments::class);
    $printThreads = $printComments->for($item ?? null);
@endphp
@if ($printThreads !== [])
    <aside class="print-comments" aria-label="{{ __('ui.comments_open_title') }}">
        @foreach ($printThreads as $thread)
            <div class="print-comments__thread">
                @foreach (collect([$thread])->concat($thread->replies) as $comment)
                    <div class="print-comments__line {{ $comment->parent_id ? 'print-comments__line--reply' : '' }}">
                        <div class="print-comments__who">
                            @unless ($comment->parent_id)<span class="print-comments__num">C{{ $printComments->numberOf($thread) }}</span>@endunless
                            <strong>{{ $comment->author?->name ?? __('ui.comments_former_user') }}</strong>
                            <span class="print-comments__when">{{ $comment->created_at?->format('Y-m-d') }}</span>
                        </div>
                        <div class="print-comments__text">{!! \App\Services\CommentService::render($comment->body) !!}</div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </aside>
@endif
