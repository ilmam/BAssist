<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Project;
use App\Services\CommentService;
use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
use App\Support\Tenancy;
use Illuminate\View\View;

/**
 * Every open comment thread of a project on one page, grouped by the record it
 * is on. Comments live on their records; this page exists so none is missed
 * (the readiness check "Open comment threads" links here).
 */
class ProjectCommentsController extends Controller
{
    public function index(Project $project, CommentService $comments): View
    {
        EntityAccess::authorize(auth()->user(), 'Project', EntityAccess::VIEW);
        Tenancy::assertProject($project);

        $user = auth()->user();
        $groups = $comments->listThreads($project, null, 'open')
            // Only threads on records this user may open.
            ->filter(fn (Comment $thread) => EntityAccess::can($user, class_basename($thread->commentable_type), EntityAccess::VIEW))
            ->groupBy(fn (Comment $thread) => $thread->commentable_type.':'.$thread->commentable_id)
            ->map(function ($threads) use ($project): array {
                $record = $threads->first()->commentable;
                $model = class_basename($record);

                return [
                    'entity' => CrudEntityRegistry::all()[$model]['nav_label'] ?? $model,
                    'is_project' => $record instanceof Project,
                    'code' => $record->getAttribute('code'),
                    'title' => $record->getAttribute('title') ?? $record->getAttribute('name'),
                    'url' => $record instanceof Project
                        ? route('projects.dashboard', $project)
                        : model_route($model, 'show', $record->getKey()),
                    'threads' => $threads,
                ];
            })
            // Project-wide threads first, then by record.
            ->sortBy(fn (array $group) => [$group['is_project'] ? 0 : 1, $group['entity'], (string) $group['code']])
            ->values();

        return view('pages.projects.comments', [
            'project' => $project,
            'groups' => $groups,
            'total' => $groups->sum(fn (array $group) => $group['threads']->count()),
        ]);
    }
}
