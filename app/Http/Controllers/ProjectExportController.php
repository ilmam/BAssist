<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectExportService;
use App\Support\EntityAccess;
use Illuminate\View\View;

class ProjectExportController extends Controller
{
    public function __construct(protected ProjectExportService $export)
    {
    }

    public function show(Project $project): View
    {
        EntityAccess::authorize(auth()->user(), 'Project', EntityAccess::VIEW);

        $tenantId = auth()->user()?->tenant_id;
        if ($tenantId !== null) {
            $project->loadMissing('workspace');

            if ((int) $project->workspace?->tenant_id !== (int) $tenantId) {
                abort(404);
            }
        }

        $pack = $this->export->build($project);

        return view('pages.projects.export', $pack);
    }
}
