{{--
    Open comment threads on the project itself (time zone, roles, retention…).
    Printed as margin notes beside the document title, exactly like an item's
    comments: the project is the item. Include it immediately before the title.
    One note per thread, so a page break falls between notes, not inside one.
    Follows the same on/off switch as item comments (?comments=0); the appendix
    lists these threads with the rest.
--}}
@php $printComments = app(\App\Support\PrintComments::class); @endphp
@foreach ($printComments->for($project ?? null) as $thread)
    <aside class="print-comments" aria-label="{{ __('ui.comments_project_title') }}">
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
    </aside>
@endforeach
