<?php

namespace App\Mcp\Tools;

use App\Services\CommentService;
use App\Services\EntityRecordService;
use App\Services\ProjectInsightsService;
use App\Support\EntityAccess;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListCommentsTool extends BAssistTool
{
    protected string $name = 'list-comments';

    protected string $title = 'List comment threads';

    protected string $description = 'Comment threads on a project\'s records: review remarks and findings raised during delivery, each with the record it is on, its status and what it is waiting for. Give project_id for the whole project, or entity and id for one record. Statuses: open (no answer yet), answered (a person replied with a decision that is not applied yet), implemented (applied, waiting for a person to verify), closed. state: answered lists the decisions waiting to be implemented. Each comment says whether you (the signed-in user) wrote it (author_is_you) and whether it came through an AI assistant (via: mcp).';

    public function handle(
        Request $request,
        CommentService $comments,
        ProjectInsightsService $insights,
        EntityRecordService $records,
    ): Response|ResponseFactory {
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer', 'required_without:entity'],
            'entity' => ['nullable', 'string', 'max:100', 'required_with:id'],
            'id' => ['nullable', 'integer', 'required_with:entity'],
            'state' => ['nullable', 'in:active,open,answered,implemented,closed,resolved,all'],
            'since' => ['nullable', 'date'],
        ]);

        return $this->respond(function () use ($validated, $comments, $insights, $records): array {
            $record = null;
            $project = null;

            if (! empty($validated['entity'])) {
                // record() checks view permission and finds the record inside the tenant.
                $record = $comments->record($records->resolve($validated['entity']), (int) $validated['id']);
            } else {
                EntityAccess::authorize(auth()->user(), 'Project', EntityAccess::VIEW);
                $project = $insights->project((int) $validated['project_id']);
            }

            $threads = $comments->listThreads(
                $project,
                $record,
                $validated['state'] ?? 'active',
                isset($validated['since']) ? Carbon::parse($validated['since']) : null,
            );

            return [
                'counts' => $comments->statusCounts($project, $record),
                'total' => $threads->count(),
                'threads' => $threads->map(fn ($thread) => $comments->threadToArray($thread))->all(),
            ];
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('All threads of this project.'),
            'entity' => $schema->string()->description('With id: only threads on this record, for example Feature.'),
            'id' => $schema->integer()->description('Record id, with entity.'),
            'state' => $schema->string()->enum(['active', 'open', 'answered', 'implemented', 'closed', 'all'])->description('active (default) is everything not closed. open = no answer yet; answered = a person has decided and it is not applied yet; implemented = applied, waiting for a person to close.'),
            'since' => $schema->string()->description('ISO date or date-time. Only threads created, replied to or resolved from then on.'),
        ];
    }
}
