<?php

namespace App\Mcp\Tools;

use App\Models\Comment;
use App\Services\CommentService;
use App\Services\EntityRecordService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly(false)]
#[IsDestructive(false)]
class AddCommentTool extends BAssistTool
{
    protected string $name = 'add-comment';

    protected string $title = 'Comment on a record';

    protected string $description = 'Post a comment on a record, or reply to a thread. Use it to raise a finding: something the requirements do not answer, state unclearly or get wrong. Put it on the record it concerns; if no record covers it yet, use the nearest parent (the stakeholder need or business need), or the Project for a project-wide matter. Say what was observed, what the system does today, a suggested answer, and whether it was verified. Mention a person with @Name to notify them. Posting text that is already on the record returns the existing comment (already_posted) instead of adding a duplicate. A comment is a note for people, not a requirement: it changes nothing in the specification, and only a person resolves it.';

    public function handle(Request $request, CommentService $comments, EntityRecordService $records): Response|ResponseFactory
    {
        $this->requireWrite($request);

        $validated = $request->validate([
            'entity' => ['required', 'string', 'max:100'],
            'id' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:'.CommentService::MAX_LENGTH],
            'reply_to' => ['nullable', 'integer'],
        ]);

        return $this->respond(function () use ($validated, $comments, $records): array {
            $record = $comments->record($records->resolve($validated['entity']), (int) $validated['id']);

            // Posting the same text on the same record twice returns the first
            // comment instead of a duplicate, so re-running a batch is harmless.
            $existing = Comment::query()
                ->where('commentable_type', $record::class)
                ->where('commentable_id', $record->getKey())
                ->where('body', trim($validated['body']))
                ->oldest()
                ->first();
            if ($existing !== null) {
                $thread = $existing->parent_id !== null ? $existing->parent()->first() : $existing;

                return ['already_posted' => true]
                    + $comments->threadToArray($thread->load(['author', 'resolver', 'replies.author', 'commentable']));
            }

            $comment = $comments->add(
                $record,
                $validated['body'],
                isset($validated['reply_to']) ? (int) $validated['reply_to'] : null,
            );

            $thread = $comment->parent_id !== null ? $comment->parent()->first() : $comment;

            return $comments->threadToArray($thread->load(['author', 'resolver', 'replies.author', 'commentable']));
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entity' => $schema->string()->description('Entity of the record to comment on, for example FunctionalRequirement or Project.')->required(),
            'id' => $schema->integer()->description('Record id.')->required(),
            'body' => $schema->string()->description('The comment, up to '.CommentService::MAX_LENGTH.' characters.')->required(),
            'reply_to' => $schema->integer()->description('Thread id to reply to. A reply re-opens a resolved thread.'),
        ];
    }
}
