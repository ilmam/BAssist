{{--
    Comment threads panel (#8). Rendered inline in details pages / view modals and
    re-rendered by CommentController after every action (comments.js swaps it).
--}}
@php
    use App\Services\CommentService;

    $threads = $threads ?? collect();
    $open = $threads->filter(fn ($t) => $t->isOpen())->values();
    $resolved = $threads->reject(fn ($t) => $t->isOpen())->values();
    $panelId = 'comments-'.\Illuminate\Support\Str::kebab($commentModel).'-'.$commentRecordId;
    $storeUrl = route('comments.store', ['model' => \Illuminate\Support\Str::snake($commentModel), 'id' => $commentRecordId]);
    $me = auth()->id();
    $initials = fn ($user) => collect(explode(' ', trim((string) ($user?->name ?? '?'))))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: '?';
    $mentionHint = collect($mentionUsers ?? [])->reject(fn ($u) => (int) $u->id === (int) $me)->take(4)
        ->map(fn ($u) => '@'.\Illuminate\Support\Str::before((string) $u->name, ' '))->implode(', ');
@endphp

<x-section class="ba-comments-section" id="{{ $panelId }}" data-comments-panel data-csrf="{{ csrf_token() }}" :title="__('ui.comments_title')" icon="messages" key="comments">
    <x-slot:badge>
        @if ($open->isNotEmpty())
            <x-status-badge tone="warning">{{ trans_choice('ui.comments_open_count', $open->count(), ['count' => $open->count()]) }}</x-status-badge>
        @elseif ($threads->isNotEmpty())
            <x-status-badge tone="success">{{ __('ui.comments_all_resolved') }}</x-status-badge>
        @endif
    </x-slot:badge>
    <x-slot:actions>
        <button type="button" class="ba-review__help ba-link-btn" data-help-url="{{ route('help.guide.show', 'collaboration') }}">{{ __('ui.review_how_it_works') }}</button>
    </x-slot:actions>
    <div class="ba-comments">

    <form class="ba-comments__form" method="post" action="{{ $storeUrl }}" data-comments-form>
        @csrf
        <label class="sr-only" for="{{ $panelId }}-body">{{ __('ui.comments_new') }}</label>
        <textarea id="{{ $panelId }}-body" name="body" rows="2" maxlength="{{ CommentService::MAX_LENGTH }}" required
                  class="kt-textarea" placeholder="{{ __('ui.comments_placeholder') }}"></textarea>
        <div class="ba-comments__form-foot">
            <span class="ba-comments__hint">
                {{ __('ui.comments_mention_hint') }}@if ($mentionHint !== '') — {{ $mentionHint }}@endif
            </span>
            <button type="submit" class="{{ ui_btn_classes('primary', 'sm') }}">{{ __('ui.comments_post') }}</button>
        </div>
    </form>

    @if ($threads->isEmpty())
        <p class="ba-comments__empty">{{ __('ui.comments_empty') }}</p>
    @endif

    @foreach ([$open, $resolved] as $groupIndex => $group)
        @continue($group->isEmpty())
        @if ($groupIndex === 1)
            <details class="ba-comments__resolved">
                <summary>{{ trans_choice('ui.comments_resolved_count', $group->count(), ['count' => $group->count()]) }}</summary>
        @endif
        <ol class="ba-thread-list">
            @foreach ($group as $thread)
                <li class="ba-thread {{ $thread->isOpen() ? '' : 'is-resolved' }}">
                    @php $threadStatus = $thread->currentStatus(); @endphp
                    <div class="ba-thread__status">
                        <x-status-badge :tone="\App\Support\CommentStatus::tone($threadStatus)">{{ __('ui.comments_status_'.$threadStatus) }}</x-status-badge>
                        <span class="ba-comments__hint">{{ __('ui.comments_waiting_'.$threadStatus) }}</span>
                    </div>
                    @foreach (collect([$thread])->concat($thread->replies) as $comment)
                        <article class="ba-comment {{ $comment->parent_id ? 'ba-comment--reply' : '' }}">
                            <span class="ba-comment__avatar" aria-hidden="true">{{ $initials($comment->author) }}</span>
                            <div class="ba-comment__main">
                                <div class="ba-comment__meta">
                                    <strong>{{ $comment->author?->name ?? __('ui.comments_former_user') }}</strong>
                                    @if ($comment->via)
                                        <span>({{ __('ui.history_via_'.$comment->via) }})</span>
                                    @endif
                                    <time datetime="{{ $comment->created_at?->toIso8601String() }}" title="{{ $comment->created_at?->format('Y-m-d H:i') }}">{{ $comment->created_at?->diffForHumans() }}</time>
                                    @if ((int) $comment->user_id === (int) $me || is_super_admin())
                                        <form method="post" action="{{ route('comments.destroy', $comment) }}" data-comments-form class="ba-comment__inline-form">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="ba-link-btn ba-link-btn--muted" data-confirm="{{ __('ui.comments_delete_confirm') }}">{{ __('ui.comments_delete') }}</button>
                                        </form>
                                    @endif
                                </div>
                                <div class="ba-comment__body">{!! CommentService::render($comment->body) !!}</div>
                            </div>
                        </article>
                    @endforeach

                    <div class="ba-thread__actions">
                        @if ($thread->isOpen())
                            <details class="ba-thread__reply">
                                <summary class="ba-link-btn">{{ __('ui.comments_reply') }}</summary>
                                <form method="post" action="{{ $storeUrl }}" data-comments-form>
                                    @csrf
                                    <input type="hidden" name="parent_id" value="{{ $thread->id }}">
                                    <label class="sr-only" for="{{ $panelId }}-reply-{{ $thread->id }}">{{ __('ui.comments_reply') }}</label>
                                    <textarea id="{{ $panelId }}-reply-{{ $thread->id }}" name="body" rows="2" required maxlength="{{ CommentService::MAX_LENGTH }}" class="kt-textarea"></textarea>
                                    <button type="submit" class="{{ ui_btn_classes('outline', 'sm') }}">{{ __('ui.comments_reply_post') }}</button>
                                </form>
                            </details>
                            @if ($threadStatus !== \App\Support\CommentStatus::IMPLEMENTED && entity_can($commentModel, \App\Support\EntityAccess::UPDATE))
                                <form method="post" action="{{ route('comments.implemented', $thread) }}" data-comments-form class="ba-comment__inline-form">
                                    @csrf
                                    <button type="submit" class="ba-link-btn">{{ __('ui.comments_mark_implemented') }}</button>
                                </form>
                            @endif
                            @if (entity_can($commentModel, \App\Support\EntityAccess::APPROVE))
                                <form method="post" action="{{ route('comments.resolve', $thread) }}" data-comments-form class="ba-comment__inline-form">
                                    @csrf
                                    <input type="hidden" name="resolved" value="1">
                                    <button type="submit" class="ba-link-btn"><i class="ki-filled ki-check" aria-hidden="true"></i>{{ __('ui.comments_resolve') }}</button>
                                </form>
                            @endif
                        @else
                            <span class="ba-comments__hint">{{ __('ui.comments_resolved_by', ['name' => $thread->resolver?->name ?? '—', 'when' => $thread->resolved_at?->diffForHumans()]) }}</span>
                            <form method="post" action="{{ route('comments.resolve', $thread) }}" data-comments-form class="ba-comment__inline-form">
                                @csrf
                                <input type="hidden" name="resolved" value="0">
                                <button type="submit" class="ba-link-btn">{{ __('ui.comments_reopen') }}</button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
        @if ($groupIndex === 1)
            </details>
        @endif
    @endforeach
    </div>
</x-section>
