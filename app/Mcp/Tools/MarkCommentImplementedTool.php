<?php

namespace App\Mcp\Tools;

use App\Models\Comment;
use App\Services\CommentService;
use App\Support\CommentStatus;
use App\Support\RequestChannel;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsIdempotent]
class MarkCommentImplementedTool extends BAssistTool
{
    protected string $name = 'mark-comment-implemented';

    protected string $title = 'Mark a comment thread implemented';

    protected string $description = 'After applying the decision a person gave in their latest reply on an answered thread, report what was changed and mark the thread implemented. It then waits for a person to verify and close it; there is no tool to close a thread. Only works when the latest answer was written by the signed-in user, and only once per answer: calling it on a thread that is already implemented changes nothing. Do the work first (update the requirements, then tests and code), then call this with a short, specific report.';

    public function handle(Request $request, CommentService $comments): Response|ResponseFactory
    {
        $this->requireWrite($request);

        $validated = $request->validate([
            'thread_id' => ['required', 'integer'],
            'report' => ['required', 'string', 'max:'.CommentService::MAX_LENGTH],
        ]);

        return $this->respond(function () use ($validated, $comments): array {
            // Tenant-scoped lookup, then the same view check as reading the record.
            $thread = Comment::query()->threads()->with('replies.author')->findOrFail((int) $validated['thread_id']);
            $record = $comments->record(class_basename($thread->commentable_type), (int) $thread->commentable_id);

            $load = fn () => $comments->threadToArray(
                $thread->fresh()->load(['author', 'resolver', 'implementer', 'replies.author', 'commentable'])
            );

            // Already reported for the current answer: nothing to do.
            if ($thread->currentStatus() === CommentStatus::IMPLEMENTED) {
                return ['already_implemented' => true] + $load();
            }

            // The instruction is the latest reply written by a person, and it must be the user's own.
            $decision = $thread->replies->reverse()->first(fn (Comment $reply) => $reply->via !== RequestChannel::MCP);
            if ($decision === null || $thread->currentStatus() !== CommentStatus::ANSWERED) {
                throw ValidationException::withMessages(['thread_id' => __('ui.comments_implement_no_decision')]);
            }
            if ((int) $decision->user_id !== (int) auth()->id()) {
                throw ValidationException::withMessages([
                    'thread_id' => __('ui.comments_implement_not_yours', ['name' => $decision->author?->name ?? '?']),
                ]);
            }

            $comments->add($record, $validated['report'], (int) $thread->id);
            $comments->markImplemented($thread->fresh());

            return $load();
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'thread_id' => $schema->integer()->description('The thread id from list-comments.')->required(),
            'report' => $schema->string()->description('What was changed, specifically: records created or revised (with their codes), and tests or code updated. Posted as a reply on the thread.')->required(),
        ];
    }
}
