<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Project;
use App\Models\User;
use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
use App\Support\RequestChannel;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Comment threads on entities (#8): create, reply, resolve, @mentions, and the
 * per-project lookup the BABOK / export PDFs use to print open threads.
 */
class CommentService
{
    public const MAX_LENGTH = 4000;

    public static function supports(string $model): bool
    {
        return \App\Support\EntityFeatures::commentable($model)
            && array_key_exists($model, CrudEntityRegistry::all());
    }

    /**
     * The commented record, resolved through its repository (tenant-scoped) after a VIEW check.
     */
    public function record(string $model, int $id): Model
    {
        abort_unless(self::supports($model), 404);
        EntityAccess::authorize(auth()->user(), $model, EntityAccess::VIEW);

        return CrudEntityRegistry::repository($model)->findModel($id);
    }

    /**
     * Threads for one record: open first (newest activity first), then resolved.
     *
     * @return Collection<int, Comment>
     */
    public function threads(Model $record): Collection
    {
        return Comment::query()
            ->threads()
            ->where('commentable_type', $record::class)
            ->where('commentable_id', $record->getKey())
            ->with(['author', 'resolver', 'replies.author'])
            ->orderByRaw('CASE WHEN resolved_at IS NULL THEN 0 ELSE 1 END')
            ->latest('updated_at')
            ->get();
    }

    public function openCount(Model $record): int
    {
        return Comment::query()
            ->threads()
            ->open()
            ->where('commentable_type', $record::class)
            ->where('commentable_id', $record->getKey())
            ->count();
    }

    public function add(Model $record, string $body, ?int $parentId = null): Comment
    {
        $body = trim($body);
        if ($body === '') {
            throw ValidationException::withMessages(['body' => __('ui.comments_body_required')]);
        }
        if (mb_strlen($body) > self::MAX_LENGTH) {
            throw ValidationException::withMessages(['body' => __('ui.comments_body_too_long', ['max' => self::MAX_LENGTH])]);
        }

        $parent = null;
        if ($parentId !== null) {
            $parent = Comment::query()->threads()->findOrFail($parentId);
            abort_unless($parent->commentable_type === $record::class && (int) $parent->commentable_id === (int) $record->getKey(), 404);
        }

        $comment = Comment::query()->create([
            'project_id' => $this->projectIdOf($record),
            'commentable_type' => $record::class,
            'commentable_id' => $record->getKey(),
            'parent_id' => $parent?->id,
            'user_id' => auth()->id(),
            'body' => $body,
            'via' => app(RequestChannel::class)->current(),
        ]);

        if ($parent !== null) {
            // A reply re-opens the discussion and bumps the thread to the top.
            $parent->forceFill(['resolved_at' => null, 'resolved_by' => null])->touch();
        }

        $mentioned = $this->mentionedUsers($body);
        if ($mentioned->isNotEmpty()) {
            $comment->mentionedUsers()->syncWithoutDetaching($mentioned->pluck('id')->all());
        }

        return $comment;
    }

    /**
     * The project a record belongs to. A project is its own owner, so a
     * comment on the project itself is a project-wide note.
     */
    public function projectIdOf(Model $record): int
    {
        return $record instanceof Project
            ? (int) $record->getKey()
            : (int) $record->getAttribute('project_id');
    }

    /**
     * Threads of a whole project or of one record, oldest first, for callers
     * outside the web UI (API, MCP). $state is open, resolved or all; $since
     * keeps threads touched (created, replied to or resolved) from that moment.
     *
     * @return Collection<int, Comment>
     */
    public function listThreads(?Project $project, ?Model $record = null, string $state = 'open', ?DateTimeInterface $since = null): Collection
    {
        return Comment::query()
            ->threads()
            ->when($project !== null, fn ($q) => $q->where('project_id', $project->getKey()))
            ->when($record !== null, fn ($q) => $q
                ->where('commentable_type', $record::class)
                ->where('commentable_id', $record->getKey()))
            ->when($state === 'open', fn ($q) => $q->open())
            ->when($state === 'resolved', fn ($q) => $q->whereNotNull('resolved_at'))
            ->when($since !== null, fn ($q) => $q->where('updated_at', '>=', $since))
            ->with(['author', 'resolver', 'replies.author', 'commentable'])
            ->oldest()
            ->get()
            ->filter(fn (Comment $thread) => $thread->commentable !== null)
            ->values();
    }

    /**
     * A thread as plain data, with a link to the record it is on.
     *
     * @return array<string, mixed>
     */
    public function threadToArray(Comment $thread): array
    {
        $record = $thread->commentable;
        $model = class_basename($thread->commentable_type);
        $line = fn (Comment $comment): array => [
            'id' => (int) $comment->id,
            'author' => $comment->author?->name,
            'via' => $comment->via,
            'body' => $comment->body,
            'created_at' => $comment->created_at?->toIso8601String(),
        ];

        return $line($thread) + [
            'state' => $thread->isOpen() ? 'open' : 'resolved',
            'resolved_at' => $thread->resolved_at?->toIso8601String(),
            'resolved_by' => $thread->resolver?->name,
            'on' => [
                'entity' => $model,
                'id' => (int) $thread->commentable_id,
                'code' => $record?->getAttribute('code'),
                'title' => $record?->getAttribute('title') ?? $record?->getAttribute('name'),
                'url' => array_key_exists($model, CrudEntityRegistry::all())
                    ? model_route($model, 'show', $thread->commentable_id)
                    : null,
            ],
            'replies' => $thread->replies->map($line)->all(),
        ];
    }

    public function setResolved(Comment $thread, bool $resolved): Comment
    {
        abort_unless($thread->parent_id === null, 404);
        $thread->forceFill([
            'resolved_at' => $resolved ? now() : null,
            'resolved_by' => $resolved ? auth()->id() : null,
        ])->save();

        return $thread;
    }

    public function delete(Comment $comment): void
    {
        abort_unless((int) $comment->user_id === (int) auth()->id() || is_super_admin(), 403);
        $comment->delete();
    }

    /**
     * Users in the same tenant named after "@": "@Ahmed" or "@Ahmed.Ali" / "@Ahmed_Ali" for full names.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function mentionedUsers(string $body): \Illuminate\Support\Collection
    {
        preg_match_all('/(?<![\w@])@([\p{L}\p{N}][\p{L}\p{N}._-]{1,40})/u', $body, $matches);
        $tokens = collect($matches[1] ?? [])->map(fn (string $t) => Str::lower(str_replace(['.', '_'], ' ', rtrim($t, '.-_'))))->unique();
        if ($tokens->isEmpty()) {
            return collect();
        }

        $users = $this->tenantUsers();

        return $tokens->map(function (string $token) use ($users) {
            $full = $users->filter(fn (User $u) => Str::lower((string) $u->name) === $token);
            if ($full->count() === 1) {
                return $full->first();
            }
            $first = $users->filter(fn (User $u) => Str::lower(Str::before((string) $u->name, ' ')) === $token);

            return $first->count() === 1 ? $first->first() : null; // ambiguous first names are ignored
        })->filter()->unique('id')->reject(fn (User $u) => (int) $u->id === (int) auth()->id())->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function tenantUsers(): \Illuminate\Support\Collection
    {
        $tenantId = auth()->user()?->tenant_id;

        return User::query()
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Open threads for every item in a project, keyed "Class:id" — one query per document.
     *
     * @return array<string, list<Comment>>
     */
    public function openThreadsForProject(Project $project): array
    {
        $map = [];
        Comment::query()
            ->threads()
            ->open()
            ->where('project_id', $project->id)
            ->with(['author', 'replies.author', 'commentable'])
            ->oldest()
            ->get()
            ->each(function (Comment $thread) use (&$map): void {
                $map[$thread->commentable_type.':'.$thread->commentable_id][] = $thread;
            });

        return $map;
    }

    /**
     * Recent unseen mentions of the current user in open threads (home page).
     *
     * @return Collection<int, Comment>
     */
    public function mentionsFor(User $user, int $limit = 6): Collection
    {
        return Comment::query()
            ->whereHas('mentionedUsers', fn ($q) => $q->where('users.id', $user->id)->whereNull('comment_mentions.seen_at'))
            ->with(['author', 'commentable', 'parent'])
            ->latest()
            ->limit($limit)
            ->get()
            ->filter(fn (Comment $c) => ($c->parent ?? $c)->isOpen() && $c->commentable !== null)
            ->values();
    }

    public function markMentionsSeen(Model $record, User $user): void
    {
        $ids = Comment::query()
            ->where('commentable_type', $record::class)
            ->where('commentable_id', $record->getKey())
            ->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }
        DB::table('comment_mentions')
            ->whereIn('comment_id', $ids)
            ->where('user_id', $user->id)
            ->whereNull('seen_at')
            ->update(['seen_at' => now()]);
    }

    /**
     * Body as safe HTML with @mentions highlighted and line breaks kept.
     */
    public static function render(string $body): string
    {
        $html = e($body);
        $html = (string) preg_replace('/(?<![\w@])@([\p{L}\p{N}][\p{L}\p{N}._-]{1,40})/u', '<span class="ba-mention">@$1</span>', $html);

        return nl2br($html);
    }
}
