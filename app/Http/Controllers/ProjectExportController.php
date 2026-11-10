<?php

namespace App\Http\Controllers;

use App\Services\CommentService;
use App\Support\PrintComments;

use App\Models\Project;
use App\Services\ProjectExportService;
use App\Support\EntityAccess;
use App\Support\Tenancy;
use Illuminate\View\View;

class ProjectExportController extends Controller
{
    public function __construct(protected ProjectExportService $export)
    {
    }

    public function show(Project $project): View
    {
        EntityAccess::authorize(auth()->user(), 'Project', EntityAccess::VIEW);

        Tenancy::assertProject($project);

        $pack = $this->export->build($project);

        // Default (or ?comments=replied) is Open only: threads already answered are hidden.
        // ?comments=1 shows all, ?comments=0 hides all.
        $commentsMode = match (request()->query('comments')) {
            '0' => 'none',
            '1' => 'all',
            default => 'replied',
        };
        $includeComments = $commentsMode !== 'none';
        $threads = $includeComments ? app(CommentService::class)->openThreadsForProject($project) : [];
        if ($commentsMode === 'replied') {
            $threads = array_filter(array_map(
                fn (array $list) => array_values(array_filter($list, fn ($t) => $t->currentStatus() !== \App\Support\CommentStatus::ANSWERED)),
                $threads,
            ));
        }
        app()->instance(PrintComments::class, new PrintComments(
            $includeComments,
            $threads,
            // Sign-off block switched off in the export pack (null = none); BABOK documents keep it.
            null,
        ));
        $pack['includeComments'] = $includeComments;
        $pack['commentsMode'] = $commentsMode;

        return view('pages.projects.export', $pack);
    }
}
